<?php
declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

// #####################################################################################################################

$temporaryPath = __DIR__ . "/../storage";
$reportFilePath = $temporaryPath . "/report_" . date("YmdHis") . ".ser";
$phpManualArchiveFilePath = $temporaryPath . "/php_manual_en.tar.gz";
$phpManualFolderPath = $temporaryPath . "/php_manual";
$documentationBaseUrl = "https://www.php.net/manual/en";

// #####################################################################################################################

/**
 * @var array e.g. Array (
 *                   [...]
 *                   [23] => Array (
 *                             [example_id] => example-5924
 *                             [code] => <?php
 *                                       $zk = new Zookeeper();
 *                                       $zk->connect('localhost:2181');
 *                                       $zk->addAuth('digest', 'timandes:timandes');
 *                                       $zkConfig = $zk->getConfig();
 *                                       $r = $zkConfig->get();
 *                                       if ($r)
 *                                           echo $r;
 *                                       else
 *                                           echo 'ERR';
 *                                       ?>
 *                             [output] => server.1=localhost:2888:3888:participant;0.0.0.0:2181
 *                                         version=0xca01e881a2
 *                             [url] => https://www.php.net/manual/en/zookeeperconfig.get.php
 *                           )
 *                   [...]
 *                 )
 */
$data = [];

decompressArchive($phpManualArchiveFilePath, $temporaryPath);

// https://stackoverflow.com/questions/13718500/using-xpath-with-php-to-parse-html
libxml_use_internal_errors(true);

$dom = new DomDocument;

foreach (glob($temporaryPath . "/php-chunked-xhtml/*.html") as $filePath) {

    $dom->loadHTMLFile($filePath);
    $exampleNodes = getExampleNodes($dom);

    if ($exampleNodes->length === 0) {
        continue;
    }

    $url = $documentationBaseUrl . "/" . preg_replace('/\.html$/', ".php", basename($filePath));
/*
    if (isUrlValid($url) === false) {
        echo "[URL " . $url . " does not exist]";
        continue;
    }
*/
    for ($i = 0; $i < $exampleNodes->length; $i++) {

        $exampleNode = $exampleNodes->item($i);

        $exampleId = $exampleNode->getAttribute("id");

        list($codeNode, $outputNode) = getCodeAndOutputNodes($exampleNode);

        // If either code or output are not available,
        // no way to further process this example.
        if ($codeNode === null || $outputNode === null) {
            continue;
        }

        $codeHtml = $codeNode->ownerDocument->saveXML($codeNode);
        $code = extractText($codeHtml);

        $outputHtml = $outputNode->ownerDocument->saveXML($outputNode);
        $output = extractText($outputHtml);

        // storeCodeOutputUrl($reportFilePath, $code, $output, $url);

        $data[] = [
            "example_id" => $exampleId,
            "code" => $code,
            "output" => $output,
            "url" => $url,
        ];

        echo ".";

    }

    unlink($filePath);

}

file_put_contents($reportFilePath, serialize($data));
