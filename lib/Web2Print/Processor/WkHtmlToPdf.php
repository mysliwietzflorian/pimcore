<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Web2Print\Processor;

use Pimcore\Config;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\PrintConfigEvent;
use Pimcore\Model\Document;
use Pimcore\Tool\Console;
use Pimcore\Web2Print\Processor;

/**
 * @deprecated and will be removed in Pimcore 11. Use either PdfReactor or HeadlessChrome instead.
 */
class WkHtmlToPdf extends Processor
{
    /**
     * @var string
     */
    private $wkhtmltopdfBin;

    /**
     * @var string
     */
    private $options = '';

    /**
     * @var \stdClass|null
     */
    protected $config;

    /**
     * @param string $wkhtmltopdfBin
     * @param array $options key => value
     */
    public function __construct($wkhtmltopdfBin = null, $options = null)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5',
            sprintf('%s has been deprecated and will be removed in Pimcore 11. Use %s or %s instead.',
                __CLASS__, PdfReactor::class, HeadlessChrome::class)
        );

        $web2printConfig = Config::getWeb2PrintConfig();

        if (!empty($wkhtmltopdfBin)) {
            $this->wkhtmltopdfBin = $wkhtmltopdfBin;
        } elseif ($web2printConfig->get('wkhtmltopdfBin')) {
            $this->wkhtmltopdfBin = $web2printConfig->get('wkhtmltopdfBin');
        } elseif ($determined = Console::getExecutable('wkhtmltopdf')) {
            $this->wkhtmltopdfBin = $determined;
        }

        if (empty($options)) {
            if ($web2printConfig->get('wkhtml2pdfOptions')) {
                $options = $web2printConfig->get('wkhtml2pdfOptions')->toArray();
            }
        }

        if ($options) {
            foreach ($options as $key => $value) {
                $this->options .= ' --' . (string)$key;
                if ($value !== null && $value !== '') {
                    $this->options .= ' ' . (string)$value;
                }
            }
        } else {
            $this->options = '';
        }
    }

    /**
     * {@internal}
     */
    protected function buildPdf(Document\PrintAbstract $document, $config)
    {
        $this->config = $config;
        $web2printConfig = Config::getWeb2PrintConfig();

        $params = ['document' => $document];
        $this->updateStatus($document->getId(), 10, 'start_html_rendering');
        $html = $document->renderDocument($params);

        $params['hostUrl'] = $config->protocol . '://' . $config->hostName;
        if ($web2printConfig->get('wkhtml2pdfHostname')) {
            $params['hostUrl'] = $config->protocol . '://' . $web2printConfig->get('wkhtml2pdfHostname');
        }

        $html = $this->processHtml($html, $params);
        $this->updateStatus($document->getId(), 40, 'finished_html_rendering');

        $this->updateStatus($document->getId(), 50, 'pdf_conversion');

        $pdf = $this->fromStringToStream($html);

        $this->updateStatus($document->getId(), 100, 'saving_pdf_document');

        return $pdf;
    }

    /**
     * {@internal}
     */
    public function getProcessingOptions()
    {
        $event = new PrintConfigEvent($this, [
            'options' => [],
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_OPTIONS);

        return (array)$event->getArgument('options');
    }

    /**
     * @param string $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@internal}
     */
    public function getPdfFromString($html, $params = [], $returnFilePath = false)
    {
        if (!empty($params['adapterConfig'])) {
            $this->setOptions($params['adapterConfig']);
        }
        $html = $this->processHtml($html, $params);

        if ($returnFilePath) {
            return $this->fromStringToFile($html, $params['dstFile']);
        } else {
            return $this->fromStringToStream($html);
        }
    }

    /**
     * @param string $htmlString
     * @param string $dstFile
     *
     * @return string
     */
    protected function fromStringToFile($htmlString, $dstFile = null)
    {
        $id = uniqid('web2print_');
        $tmpHtmlFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $id . '.htm';
        file_put_contents($tmpHtmlFile, $htmlString);
        $pdfFile = $this->convert($tmpHtmlFile, $dstFile);

        @unlink($tmpHtmlFile);

        return $pdfFile;
    }

    /**
     * @param string $htmlString
     *
     * @return string
     */
    protected function fromStringToStream($htmlString)
    {
        $tmpFile = $this->fromStringToFile($htmlString);
        $stream = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $stream;
    }

    /**
     * @param string $srcUrl
     * @param string|null $dstFile
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function convert($srcUrl, $dstFile = null)
    {
        if (empty($dstFile)) {
            $dstFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . uniqid('web2print_') . '.pdf';
        }

        if (empty($srcUrl) || empty($this->wkhtmltopdfBin)) {
            throw new \Exception('srcUrl || wkhtmltopdfBin is empty!');
        }

        $retVal = 0;

        $event = new PrintConfigEvent($this, [
            'wkhtmltopdfBin' => $this->wkhtmltopdfBin,
            'options' => $this->options,
            'srcUrl' => $srcUrl,
            'dstFile' => $dstFile,
            'config' => $this->config,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::PRINT_MODIFY_PROCESSING_CONFIG);

        $params = $event->getArguments();
        $cmd = $params['cmd'] ?? null;

        if (!$cmd) {
            $cmd = $params['wkhtmltopdfBin'] . ' ' . $params['options'] . ' ' . escapeshellarg($params['srcUrl']) . ' ' . escapeshellarg($params['dstFile']);
        }

        exec($cmd, $output, $retVal);

        if ($retVal != 0 && $retVal != 1) {
            throw new \Exception('wkhtmltopdf reported error (' . $retVal . "):\n" . implode("\n", $output) . "\ncommand was: " . $cmd);
        }

        return $dstFile;
    }
}
