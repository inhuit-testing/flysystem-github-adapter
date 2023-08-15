<?php

namespace Inhuit\FlysystemGithubAdapter;

use DateTimeImmutable;
use Github\Api\GitData;
use Github\Api\Repo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class GithubAdapter implements FilesystemAdapter
{
    public function __construct(
        private Repo $repo,
        private GitData $gitData,
        private string $username,
        private string $repository,
        public ?string $reference = null,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function fileExists(string $path): bool
    {
        if (\in_array($path, ['/', ''], true)) {
            return false;
        }

        $contents = $this->repo->contents();

        $exists = $contents->exists(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        if (!$exists) {
            return false;
        }

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return ($content['type'] ?? '') === 'file';
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     */
    public function directoryExists(string $path): bool
    {
        if (\in_array($path, ['/', ''], true)) {
            return true;
        }

        $contents = $this->repo->contents();

        $exists = $contents->exists(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        if (!$exists) {
            return false;
        }

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return \array_is_list($content);
    }

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function write(string $path, string $contents, Config $config): void
    {
    }

    /**
     * @param resource $contents
     *
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
    }

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function read(string $path): string
    {
        $contents = $this->repo->contents();

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return base64_decode($content['content'], true);
    }

    /**
     * @return resource
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function readStream(string $path)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemException
     */
    public function delete(string $path): void
    {
    }

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void
    {
    }

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemException
     */
    public function createDirectory(string $path, Config $config): void
    {
    }

    /**
     * @throws InvalidVisibilityProvided
     * @throws FilesystemException
     */
    public function setVisibility(string $path, string $visibility): void
    {
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function visibility(string $path): FileAttributes
    {
        $contents = $this->repo->contents();

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return $this->getFileAttributes($content);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function mimeType(string $path): FileAttributes
    {
        $contents = $this->repo->contents();

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return $this->getFileAttributes($content);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(string $path): FileAttributes
    {
        $contents = $this->repo->contents();

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return $this->getFileAttributes($content);
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function fileSize(string $path): FileAttributes
    {
        $contents = $this->repo->contents();

        $content = $contents->show(
            $this->username,
            $this->repository,
            $path,
            $this->reference
        );

        return $this->getFileAttributes($content);
    }

    /**
     * @return iterable<StorageAttributes>
     *
     * @throws FilesystemException
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $items = $this->getItems($path, $deep);

        foreach ($items as $item) {
            if (\in_array($item['type'] ?? '', ['file', 'blob'], true)) {
                yield $this->getFileAttributes($item);

                continue;
            }

            yield new DirectoryAttributes(
                $item['path'],
                null,
                null,
            );
        }
    }

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, Config $config): void
    {
    }

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, Config $config): void
    {
    }

    private function getItems(string $path, bool $deep): array
    {
        if (!$deep) {
            $contents = $this->repo->contents();

            return $contents->show(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            );
        }

        $trees = $this->gitData->trees();
        $data = $trees->show(
            $this->username,
            $this->repository,
            $this->reference ?: 'main',
            true
        );

        return $data['tree'];
    }

    private function getFileAttributes(array $content): FileAttributes
    {
        $detector = new ExtensionMimeTypeDetector();

        return new FileAttributes(
            $content['path'],
            $content['size'],
            null,
            $this->getFileTimestamp($content['path']),
            $detector->detectMimeTypeFromPath($content['path']) ?: '',
        );
    }

    private function getFileTimestamp(string $path): int
    {
        $commits = $this->repo->commits();

        $commit = $commits->all($this->username, $this->repository, [
            'page' => 1,
            'path' => $path,
            'per_page' => 1,
            'reference' => $this->reference,
        ]);

        if (empty($commit[0]['commit']['committer']['date'])) {
            return 0;
        }

        try {
            $datetime = DateTimeImmutable::createFromFormat(
                'YYYY-MM-DDTHH:MM:SSZ',
                $commit[0]['commit']['committer']['date']
            );

            return $datetime->getTimestamp();
        } catch (\Throwable) {
            return 0;
        }
    }
}
