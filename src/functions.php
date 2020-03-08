<?php
declare(strict_types=1);

/**
 * @param string $archiveFilePath
 * @param string $destinationPath
 */
function decompressArchive(string $archiveFilePath, string $destinationPath)
{
    $p = new PharData($archiveFilePath);
    $p->extractTo($destinationPath, null, true);
    unset($p);
}

/**
 * @param DomDocument $document
 *
 * @return DomNodeList
 *
 * @see https://stackoverflow.com/questions/26366391/xpath-selecting-attributes-using-starts-with?rq=1
 */
function getExampleNodes(DomDocument $document): DomNodeList
{
    $xpath = new DomXPath($document);

    // Query all <div> nodes with id attribute starting with "example-"
    $exampleNodes = $xpath->query("//div[starts-with(@id, 'example-')]");

    unset($xpath);

    return $exampleNodes;
}

function isUrlValid(string $url): bool
{
    $headers = get_headers($url);
    $result = is_array($headers) === true &&
        isset($headers[0]) === true &&
        is_string($headers[0]) === true &&
        substr($headers[0], -6) === "200 OK";
    return $result;
}

/**
 * @param DOMNode $exampleNode
 *
 * @return array
 *
 * @see https://stackoverflow.com/questions/2234441/creating-a-domdocument-from-a-domnode-in-php
 */
function getCodeAndOutputNodes(DOMNode $exampleNode): array
{
    $dom = new DomDocument;
    $dom->appendChild($dom->importNode($exampleNode, true));
    $xpath = new DomXPath($dom);

    $codeNodes = $xpath->query("//code");
    $outputNodes = $xpath->query("//pre");

    unset($xpath);

    return [
        $codeNodes->length > 0 ? $codeNodes->item(0) : null,
        $outputNodes->length > 0 ? $outputNodes->item(0) : null,
    ];
}

/**
 * @param string $htmlTagContent e.g. <code>
 *                                      <span style="color: #000000">
 *                                        <span style="color: #0000BB">&lt;?php<br/>$insert </span>
 *                                        <span style="color: #007700">= </span>
 *                                        <span style="color: #0000BB">$db</span>
 *                                        <span style="color: #007700">-&gt;</span>
 *                                        <span style="color: #0000BB">prepare</span>
 *                                        <span style="color: #007700">(</span>
 *                                        <span style="color: #DD0000">"SELECT * FROM table"</span>
 *                                        <span style="color: #007700">);<br/></span>
 *                                        <span style="color: #0000BB">?&gt;</span>
 *                                      </span>
 *                                    </code>
 *
 * @return string e.g. <?php
 *                     $insert = $db->prepare("SELECT * FROM table");
 *                     ?>
 */
function extractText(string $htmlTagContent): string
{

    $text = $htmlTagContent;

    // Fix %C2%A0 sequence, which is $nbsp;
    // https://stackoverflow.com/questions/12837682/non-breaking-utf-8-0xc2a0-space-and-preg-replace-strange-behaviour
    $text = bin2hex($text);
    $text = str_replace("c2a0", "20", $text);
    $text = hex2bin($text);

    $text = str_replace(["<br />", "<br/>", "<br>"], PHP_EOL, $text);

    $text = strip_tags($text);
    $text = html_entity_decode($text);

    $text = trim($text);

    return $text;
}