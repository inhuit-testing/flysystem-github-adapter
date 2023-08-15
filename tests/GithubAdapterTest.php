<?php

declare(strict_types=1);

namespace Inhuit\FlysystemGithubAdapter;

use Github\Api\GitData;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class GithubAdapterTest extends TestCase
{
    private MockObject $repo;
    private MockObject $gitData;
    private string $username;
    private string $repository;
    private ?string $reference = null;

    private GithubAdapter $adapter;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(Repo::class);
        $this->gitData = $this->createMock(GitData::class);
        $this->username = sha1((string) mt_rand());
        $this->repository = sha1((string) mt_rand());
        $this->reference = sha1((string) mt_rand());

        $this->adapter = new GithubAdapter(
            $this->repo,
            $this->gitData,
            $this->username,
            $this->repository,
            $this->reference,
        );
    }

    public function testExistsRoot(): void
    {
        $this->assertTrue($this->adapter->directoryExists('/'));
        $this->assertTrue($this->adapter->directoryExists(''));

        $this->assertFalse($this->adapter->fileExists('/'));
        $this->assertFalse($this->adapter->fileExists(''));
    }

    public function testFileExistsOnNotExistingItem(): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';

        $contents
            ->expects($this->once())
            ->method('exists')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn(false);

        $contents
            ->expects($this->never())
            ->method('show');

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $this->assertFalse($this->adapter->fileExists($path));
    }

    #[TestWith(['directory', false])]
    #[TestWith(['file', true])]
    public function testFileExistsOnItem(string $type, bool $expected): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';

        $contents
            ->expects($this->once())
            ->method('exists')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn(true);

        $contents
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn([
                'type' => $type,
            ]);

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $this->assertSame($expected, $this->adapter->fileExists($path));
    }

    public function testDirectoryExistsOnNotExistingItem(): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';

        $contents
            ->expects($this->once())
            ->method('exists')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn(false);

        $contents
            ->expects($this->never())
            ->method('show');

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $this->assertFalse($this->adapter->directoryExists($path));
    }

    #[TestWith([['type' => 'file'], false])]
    #[TestWith([['foo', 'bar'], true])]
    public function testDirectoryExistsOnItem(array $output, bool $expected): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';

        $contents
            ->expects($this->once())
            ->method('exists')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn(true);

        $contents
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn($output);

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $this->assertSame($expected, $this->adapter->directoryExists($path));
    }

    public function testWrite(): void
    {
        $this->assertTrue(true);
    }

    public function testWriteStream(): void
    {
        $this->assertTrue(true);
    }

    public function testRead(): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';
        $content = 'lorem ipsum';

        $contents
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn([
                'content' => base64_encode($content),
            ]);

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $this->assertSame($content, $this->adapter->read($path));
    }

    public function testReadStream(): void
    {
        /**
         * @var MockObject $contents
         */
        $contents = $this->createMock(Contents::class);

        $path = '/test';
        $content = 'lorem ipsum';

        $contents
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->username,
                $this->repository,
                $path,
                $this->reference
            )
            ->willReturn([
                'content' => base64_encode($content),
            ]);

        $this->repo
            ->expects($this->once())
            ->method('contents')
            ->willReturn($contents);

        $resource = $this->adapter->readStream($path);

        $this->assertIsResource($resource);
        $this->assertSame($content, stream_get_contents($resource));
    }

    public function testDelete(): void
    {
        $this->assertTrue(true);
    }

    public function testDeleteDirectory(): void
    {
        $this->assertTrue(true);
    }

    public function testCreateDirectory(): void
    {
        $this->assertTrue(true);
    }

    public function testSetVisibility(): void
    {
        $this->assertTrue(true);
    }

    public function testVisibility(): void
    {
        $this->assertTrue(true);
    }

    public function testMimeType(): void
    {
        $this->assertTrue(true);
    }

    public function testLastModified(): void
    {
        $this->assertTrue(true);
    }

    public function testFileSize(): void
    {
        $this->assertTrue(true);
    }

    public function testListContents(): void
    {
        $this->assertTrue(true);
    }

    public function testMove(): void
    {
        $this->assertTrue(true);
    }

    public function testCopy(): void
    {
        $this->assertTrue(true);
    }
}

