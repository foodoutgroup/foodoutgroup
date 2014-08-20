<?php

namespace Pirminis;

trait XPath
{
    protected function xpath(\SimpleXMLElement $dom, $path)
    {
        $list = $dom->xpath($path);
        return $list;
    }

    public function xpath_first(\SimpleXMLElement $dom, $path)
    {
        list($first) = $this->xpath($dom, $path) + array(null);
        return (string)$first;
    }
}
