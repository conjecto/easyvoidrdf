<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */

/**
 * Specific Dataset class for BigData
 */
class EasyVoIDRdf_Dataset_Bigdata extends EasyVoIDRdf_Dataset
{
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
        $client->setHeaders('Content-Type', 'text/turtle');
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
    public function performDelete($delete = null)
    {
        if($delete instanceof \EasyRdf_Graph) {
            $client = $this->getHttpClient('POST', array('delete' => '1'));
            $client->setRawData($delete->serialise('turtle'));
        } else {
            $client = $this->getHttpClient('DELETE', array('query' => $delete));
        }
        return $this->performHttpRequest($client);
    }

    /**
     * {@inheritdoc}
     */
    public function performInsert($insert = null)
    {
        if($insert instanceof \EasyRdf_Graph) {
            $client = $this->getHttpClient('POST');
            $client->setRawData($insert->serialise('turtle'));
        } else {
            $sparql = "INSERT DATA { ".$insert->serialise("ntriples")." }";
            $client = $this->getHttpClient('POST', array('update' => $sparql));
        }
        return $this->performHttpRequest($client);
    }
}
