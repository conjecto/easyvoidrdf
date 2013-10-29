<?php
/**
 * Auteur: Blaise de Carné - blaise@concretis.com
 */
class EasyVoIDRdf_Graph extends EasyRdf_Graph
{
    /**
     * Return a new graph containing the resource and associated bnodes
     *
     * @param  string  $uri      The URI of the resource
     * @return EasyRdf_Graph
     */
    public function resourceGraph($uri)
    {
        $resource = $this->resource($uri);
        $resourceGraph = new \EasyRdf_Graph($resource->getUri());
        $resClass = $this->classForResource($uri);
        $newResource = new $resClass($uri, $resourceGraph);

        $copyProperties = function($resource, &$newResource) use ($resourceGraph, &$copyProperties) {
            foreach($resource->properties() as $property) {
                $values = $resource->all($property);
                foreach($values as $value) {
                    $newResource->add($property, $value);
                    if($value instanceof \EasyRdf_Resource && $value->isBNode()) {
                        $resClass = $this->classForResource($value->getUri());
                        $bnode = new $resClass($value->getUri(), $resourceGraph);
                        $copyProperties($value, $bnode);
                    }
                }
            }
        };

        $copyProperties($resource, $newResource);
        return $resourceGraph;
    }
}
