<?php

namespace Neoxia\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Routing\ResponseFactory as BaseResponseFactory;

class ResponseFactory extends BaseResponseFactory
{
    /**
     * Return a new CSV response from the application.
     *
     * @param  \Illuminate\Support\Collection|array|string  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  string  $encoding
     * @return \Illuminate\Http\Response
     */
    public function csv($data, $delimiter=',', $isQuoted = false, $status = 200, $headers = [], $encoding = 'WINDOWS-1252')
    {
        if ($this->dataIsEmpty($data)) {
            return $this->make('No Content', 204);
        }

        $csv = $this->formatCsv($data, $delimiter, $isQuoted, $encoding);
        $headers = $this->createCsvHeaders($headers, $encoding);

        return $this->make($csv, $status, $headers);
    }

    /**
     * Check if an array, a string or a Collection is empty
     *
     * @param  \Illuminate\Support\Collection|array|string  $data
     * @return bool
     */
    protected function dataIsEmpty($data)
    {
        if ($data instanceof Collection) {
            return $data->isEmpty();
        } else {
            return empty($data);
        }
    }

    /**
     * Convert any data into a CSV string
     *
     * @param  \Illuminate\Support\Collection|array|string  $data
     * @param  string  $encoding
     * @return string
     */
    protected function formatCsv($data, $delimiter, $isQuoted, $encoding)
    {
        if (is_string($data)) {
            $csv = $data;
        } else {        
            $csvArray = [];

            $this->addHeaderToCsvArray($csvArray, $data, $delimiter, $isQuoted);
            $this->addRowsToCsvArray($csvArray, $data, $delimiter, $isQuoted);

            $csv = implode("\r\n", $csvArray);
        }

        return mb_convert_encoding($csv, $encoding);
    }

    /**
     * Add a CSV header to an array based on data
     *
     * @param  array  $csvArray
     * @param  \Illuminate\Support\Collection|array  $data
     * @return void
     */
    protected function addHeaderToCsvArray(&$csvArray, $data, $delimiter, $isQuoted)
    {
        $firstRowData = $this->getRowData($data[0]);

        if (Arr::isAssoc($firstRowData)) {
            $rowData = array_keys($firstRowData);

            $csvArray[0] = $this->rowDataToCsvString($rowData, $delimiter, $isQuoted);
        }
    }

    /**
     * Add CSV rows to an array based on data
     *
     * @param  array  $csvArray
     * @param  \Illuminate\Support\Collection|array  $data
     * @return void
     */
    protected function addRowsToCsvArray(&$csvArray, $data, $delimiter, $isQuoted)
    {
        foreach ($data as $row) {
            $rowData = $this->getRowData($row);

            $csvArray[] = $this->rowDataToCsvString($rowData, $delimiter, $isQuoted);
        }
    }

    /**
     * Get an array of data for CSV from a mixed input
     *
     * @param  object|array  $row
     * @return array
     */
    protected function getRowData($row)
    {
        if (is_object($row)) {
            return $row->csvSerialize();
        } else {
            return $row;
        }
    }

    /**
     * Escape quotes and join cells of an array to make a csv row string
     *
     * @param  array  $row
     * @return string
     */
    protected function rowDataToCsvString($row, $delimiter, $isQuoted)
    {
        if($isQuoted) {
            array_walk($row, function (&$cell) {
                $cell = '"' . str_replace('"', '""', $cell) . '"';
            });
        }

        return implode($delimiter, $row);
    }

    /**
     * Get HTTP headers for a CSV response
     *
     * @param  array  $customHeaders
     * @param  string  $encoding
     * @return void
     */
    protected function createCsvHeaders($customHeaders, $encoding)
    {
        $baseHeaders = [
            'Content-Type' => 'text/csv; charset=' . $encoding,
            'Content-Encoding' => $encoding,
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
        ];

        return array_merge($baseHeaders, $customHeaders);
    }
}
