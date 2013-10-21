<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

/**
 * Specific Dataset class for BigData
 */
class EasyVoIDRdf_Dataset_BigdataDataset extends EasyVoIDRdf_Dataset
{
    const DESCRIBE_QUERY = 'DESCRIBE <uri> { <http://www.bigdata.com/queryHints#Query> <http://www.bigdata.com/queryHints#describeMode> "CBD" }';

    /**
     * Prepare a specific http client
     *
     * @param string $method
     * @param array $params
     * @return \EasyRdf_Http_Client
     */
    protected function getHttpClient($method = 'GET', $params = array()) {
        $client = EasyRdf_Http::getDefaultHttpClient();
        $uri = $this->get('void:sparqlEndpoint')->getUri();
        $client->resetParameters();
        // Tell the server which response formats we can parse
        $accept = EasyRdf_Format::getHttpAcceptHeader(
            array(
                'application/sparql-results+json' => 1.0,
                'application/sparql-results+xml' => 0.8
            )
        );
        $client->setHeaders('Accept', $accept);
        $client->setMethod($method);
        $client->setUri($uri);
        foreach($params as $name => $value) {
            $client->setParameterGet($name, $value);
        }
        return $client;
    }

    /**
     * Perform a EasyRdf_Http_Client request
     * @param EasyRdf_Http_Client $client
     * @return bool
     * @throws EasyRdf_Exception
     */
    protected function performHttpRequest(\EasyRdf_Http_Client &$client) {
        $response = $client->request();
        if($response->isSuccessful()) {
            return true;
        } else {
            throw new EasyRdf_Exception(
                "HTTP request for SPARQL query failed: ".$response->getBody()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($query)
    {
        // Check for undefined prefixes
        $prefixes = '';
        foreach (EasyRdf_Namespace::namespaces() as $prefix => $uri) {
            if (strpos($query, "$prefix:") !== false and
              strpos($query, "PREFIX $prefix:") === false) {
                $prefixes .=  "PREFIX $prefix: <$uri>\n";
            }
        }
        $client = $this->getHttpClient('POST', array('update' => $prefixes . $query));
        $client->setHeaders('Content-Length', 0);
        return $this->performHttpRequest($client);
    }

    /**
     * {@inheritdoc}
     * @see http://sourceforge.net/apps/mediawiki/bigdata/index.php?title=NanoSparqlServer
     */
    public function updateData($operation, EasyRdf_Graph $data, $graphUri = null)
    {
        if($operation == "delete") {
            $client = $this->getHttpClient('POST', array('delete' => '1'));
            $client->setRawData($this->convertToTriples($data));
            $client->setHeaders('Content-Type', 'text/plain');
            return $this->performHttpRequest($client);
        }
        elseif($operation == "insert") {
            $client = $this->getHttpClient('POST');
            $client->setRawData($this->convertToTriples($data));
            $client->setHeaders('Content-Type', 'text/plain');
            return $this->performHttpRequest($client);
        }
    }
}
