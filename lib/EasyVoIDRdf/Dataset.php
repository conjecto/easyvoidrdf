<?php

/**
 * EasyVoIDRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyVoIDRdf
 * @copyright  Conjecto - Blaise de Carné
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Class that represents an VOID Dataset
 *
 * @package    EasyVoIDRdf
 * @copyright  Conjecto - Blaise de Carné
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyVoIDRdf_Dataset extends EasyVoIDRdf_Resource
{
    protected $client;
    const DESCRIBE_QUERY = "DESCRIBE <uri>";

    /**
     * Load the graph from void:dataDump
     */
   protected function loadDataDumpGraph()
    {
        $dataDumps = $this->all('void:dataDump');
        if($dataDumps) {
            $graph = new EasyVoIDRdf_Graph($this->getUri());
            foreach($dataDumps as $dump) {
                if(preg_match("/^http:/", $dump)) {
                    $graph->load($dump);
                } else {
                    $graph->parseFile($dump);
                }
            }
            return $graph;
        } else {
            return false;
        }
    }

    /**
     * Check URIspage or regex to compare given URI
     * @param $uri
     * @return bool
     */
    public function uriMatch($uri)
    {
        // void:uriSpace
        $spaces = $this->all('void:uriSpace');
        foreach($spaces as $space) {
            if(preg_match("/^".preg_quote($space->getValue(), "/")."/", $uri))
                return true;
        }
        // void:uriRegexPattern
        $regexPatterns = $this->all('void:uriRegexPattern');
        foreach($regexPatterns as $regexPattern) {
            if(preg_match("/".$regexPattern."/", $uri))
                return true;
        }
        return false;
    }

    /**
     * Initialize a SPARQL Client
     * @return EasyRdf_Sparql_Client
     * @throws Exception
     */
    protected function getSparqlClient()
    {
        if(!isset($this->client)) {
            $sparqlEndpoint = $this->get('void:sparqlEndpoint');
            if(!$sparqlEndpoint) {
                throw new Exception('You cannot perform SPARQL query on dataset without a sparqlEndpoint property.');
            }
            $this->client = new EasyRdf_Sparql_Client($sparqlEndpoint);
        }
        return $this->client;
    }

    /**
     * Perform a SPARQL query on the dataset
     * @param $query
     * @return object
     * @throws Exception
     */
    public function query($query)
    {
        return $this->getSparqlClient()->query($query);
    }

    /**
     * Perform a SPARQL update query on the dataset
     * @param $query
     * @return object
     * @throws Exception
     */
    public function update($query)
    {
        return $this->getSparqlClient()->update($query);
    }

    /**
     * Perform an UPDATE operation with graph
     * @param string $operation
     * @param EasyRdf_Graph $data
     * @param null $graphUri
     * @return object
     */
    public function updateData($operation, EasyRdf_Graph $data, $graphUri = null)
    {
        $query = strtoupper($operation) . " DATA {";
        if ($graphUri) {
            $query .= "GRAPH <$graphUri> {";
        }
        $query .= $this->convertToTriples($data);
        if ($graphUri) {
            $query .= "}";
        }
        $query .= '}';
        return $this->update($query);
    }

    /**
     * Perform a insert & delete graph operation
     *
     * @param EasyRdf_Graph  $delete
     * @param EasyRdf_Graph  $insert
     * @param null $graphUri
     * @return bool|object
     */
    public function deleteInsertData(EasyRdf_Graph $delete = null, EasyRdf_Graph $insert = null, $graphUri = null)
    {
        if($delete && $insert) {
            $query = "DELETE { ".$this->convertToTriples($delete)." } INSERT { ".$this->convertToTriples($insert)." } WHERE { }";
            if($graphUri) {
                $query = "WITH <$graphUri> ".$query;
            }
            return $this->update($query);
        } elseif($delete) {
            return $this->updateData("delete", $delete, $graphUri);
        } elseif($insert) {
            return $this->updateData("insert", $delete, $graphUri);
        }
        return false;
    }

    /**
     * loadResource
     * @param $uri
     * @return EasyRdf_Resource
     */
    public function loadResource($uri)
    {
        // void:uriLookupEndpoint
        $uriLookupEndpoint = $this->get('void:uriLookupEndpoint');
        if($uriLookupEndpoint) {
            // @todo
        }

        // void:sparqlEndpoint
        $sparqlEndpoint = $this->get('void:sparqlEndpoint');
        if($sparqlEndpoint) {
            //$query = 'DESCRIBE <'.$uri.'>';
            $query = preg_replace('/\<uri\>/si', '<'.$uri.'>', $this::DESCRIBE_QUERY);
            $client = new \EasyRdf_Sparql_Client($sparqlEndpoint);
            return $client->query($query)->resource($uri);
        }

        // void:dataDump
        $graph = $this->loadDataDumpGraph();
        if($graph) {
            $resourceGraph = $graph->resourceGraph($uri);
            return $resourceGraph->resource($uri);
        }

        return false;
    }

    /**
     * Delete a resource from the dataset
     *
     * @param $uri
     * @param null $graphUri
     * @param bool $asReference
     * @return object
     */
    public function deleteResource($uri, $graphUri = null, $asReference = true)
    {
        $queries = array();
        $patterns = array("<$uri> ?p ?o");
        if($asReference) {
            $patterns[] = "?s <$uri> ?o";
            $patterns[] = "?s ?p <$uri>";
        }

        foreach($patterns as $pattern) {
            $query = "DELETE WHERE {";
            if ($graphUri) {
                $query .= "GRAPH <$graphUri> {";
            }
            $query .= $pattern;
            if ($graphUri) {
                $query .= "}";
            }
            $query .= "}";
            $queries[] = $query;
        }

        $query = join("; ", $queries);
        return $this->update($query);
    }

    /**
     * @param $data
     * @return string
     * @throws EasyRdf_Exception
     */
    protected function convertToTriples($data)
    {
        if (is_string($data)) {
            return $data;
        } elseif (is_object($data) and $data instanceof EasyRdf_Graph) {
            # FIXME: insert Turtle when there is a way of seperateing out the prefixes
            return $data->serialise('ntriples');
        } else {
            throw new EasyRdf_Exception(
                "Don't know how to convert to triples for SPARQL query"
            );
        }
    }
}
