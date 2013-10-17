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
    /**
     * Load the graph from void:dataDump
     */
    function loadDataDumpGraph()
    {
        $dataDumps = $this->all('void:dataDump');
        if($dataDumps) {
            $graph = new EasyRdf_Graph();
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
    function uriMatch($uri)
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
     * lookup
     * @param $uri
     * @return EasyRdf_Graph
     */
    function lookup($uri)
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
            $query = 'DESCRIBE <'.$uri.'> { <http://www.bigdata.com/queryHints#Query> <http://www.bigdata.com/queryHints#describeMode> "CBD" }';
            $client = new \EasyRdf_Sparql_Client($sparqlEndpoint);
            return $client->query($query);
        }

        // void:dataDump
        $graph = $this->loadDataDumpGraph();
        if($graph) {
            return $graph->resource($uri);
        }

        return false;
    }

    /**
     * Perform a SPARQL query on the dataset
     * @param $sparql
     * @return object
     * @throws Exception
     */
    function query($sparql)
    {
        $sparqlEndpoint = $this->get('void:sparqlEndpoint');
        if(!$sparqlEndpoint) {
            throw new Exception('You cannot perform SPARQL query on dataset without sparqlEndpoint !');
        }
        $client = new EasyRdf_Sparql_Client($sparqlEndpoint);
        return $client->query($sparql);
    }
}
