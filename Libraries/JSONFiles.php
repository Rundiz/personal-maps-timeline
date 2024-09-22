<?php


namespace PMTL\Libraries;


class JSONFiles
{


    /**
     * @var string Folder path that contain JSON files.
     */
    protected $folderPath;


    /**
     * @var null|string JSON file name to search for. Set to `null` to use all JSON files.
     */
    protected $jsonFile;


    /**
     * Working on Google maps timeline exported JSON files.
     *
     * @param string $folderPath Folder path that contain JSON files.
     * @param string|null $jsonFile JSON file name to search for. Set to `null` (default) to use all JSON files.
     */
    public function __construct(string $folderPath, ?string $jsonFile = null)
    {
        $this->folderPath = $folderPath;
        $this->jsonFile = $jsonFile;
    }// __construct


    /**
     * Get JSON files that was sorted order by file name ascending.
     *
     * @return array Return array of JSON files found.
     */
    public function getFiles(): array
    {
        $files = [];

        $FI = new \FilesystemIterator($this->folderPath, \FilesystemIterator::SKIP_DOTS);
        foreach ($FI as $FileInfo) {
            if (strtolower($FileInfo->getExtension()) !== 'json') {
                continue;
            }
            if (is_string($this->jsonFile) && $this->jsonFile !== '' && $FileInfo->getFilename() !== $this->jsonFile) {
                continue;
            }
            $files[] = $FileInfo->getRealPath();
        }// endforeach;
        unset($FileInfo);
        unset($FI);

        natsort($files);

        return $files;
    }// getFiles


}// JSONFiles