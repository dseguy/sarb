<?php

/**
 * Static Analysis Results Baseliner (sarb).
 *
 * (c) Dave Liddament
 *
 * For the full copyright and licence information please view the LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace DaveLiddament\StaticAnalysisResultsBaseliner\Plugins\PsalmJsonResultsParser;

use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Common\FileName;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Common\LineNumber;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Common\Location;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Common\Type;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\File\InvalidFileFormatException;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\ResultsParser\AnalysisResult;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\ResultsParser\AnalysisResults;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\ResultsParser\Identifier;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\ResultsParser\ResultsParser;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils\ArrayParseException;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils\ArrayUtils;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils\JsonParseException;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils\JsonUtils;
use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils\ParseAtLocationException;

class PsalmJsonResultsParser implements ResultsParser
{
    const LINE_FROM = 'line_from';
    const TYPE = 'type';
    const FILE = 'file_name';

    /**
     * {@inheritdoc}
     */
    public function convertFromString(string $resultsAsString): AnalysisResults
    {
        try {
            $asArray = JsonUtils::toArray($resultsAsString);
        } catch (JsonParseException $e) {
            throw new InvalidFileFormatException('Not a valid JSON format');
        }

        return $this->convertFromArray($asArray);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToString(AnalysisResults $analysisResults): string
    {
        $asArray = [];
        foreach ($analysisResults->getAnalysisResults() as $analysisResult) {
            $asArray[] = JsonUtils::toArray($analysisResult->getFullDetails());
        }

        return JsonUtils::toString($asArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): Identifier
    {
        return new PsalmJsonIdentifier();
    }

    /**
     * {@inheritdoc}
     *
     * TODO can this become private?
     */
    public function convertFromArray(array $analysisResultsAsArray): AnalysisResults
    {
        $analysisResults = new AnalysisResults();

        $resultsCount = 0;

        /** @psalm-suppress MixedAssignment */
        foreach ($analysisResultsAsArray as $analysisResultAsArray) {
            ++$resultsCount;
            try {
                ArrayUtils::assertArray($analysisResultAsArray);
                $analysisResult = $this->convertAnalysisResultFromArray($analysisResultAsArray);
                $analysisResults->addAnalysisResult($analysisResult);
            } catch (ArrayParseException | JsonParseException $e) {
                throw new ParseAtLocationException("Result [$resultsCount]", $e);
            }
        }

        return $analysisResults;
    }

    /**
     * @param array $analysisResultAsArray
     *
     * @throws ArrayParseException
     * @throws JsonParseException
     *
     * @return AnalysisResult
     */
    private function convertAnalysisResultFromArray(array $analysisResultAsArray): AnalysisResult
    {
        $fileNameAsString = ArrayUtils::getStringValue($analysisResultAsArray, self::FILE);
        $lineAsInt = ArrayUtils::getIntValue($analysisResultAsArray, self::LINE_FROM);
        $typeAsString = ArrayUtils::getStringValue($analysisResultAsArray, self::TYPE);

        $location = new Location(
            new FileName($fileNameAsString),
            new LineNumber($lineAsInt)
        );

        return new AnalysisResult(
            $location,
            new Type($typeAsString),
            JsonUtils::toString($analysisResultAsArray)
        );
    }
}
