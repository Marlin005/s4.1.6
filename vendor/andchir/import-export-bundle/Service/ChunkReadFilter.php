<?php

namespace Andchir\ImportExportBundle\Service;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;
    private $worksheetName = '';

    /**
     * Set the list of rows that we want to read.
     *
     * @param int $startRow
     * @param int $endRow
     * @param string $worksheetName
     */
    public function setRows($startRow, $endRow, $worksheetName = '')
    {
        $this->startRow = $startRow;
        $this->endRow = $endRow;
        $this->worksheetName = $worksheetName;
    }

    public function readCell($column, $row, $worksheetName = '')
    {
        if ($worksheetName == $this->worksheetName
            && $row >= $this->startRow
            && $row <= $this->endRow) {
                return true;
        }
        return false;
    }
}
