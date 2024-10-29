<?php

declare(strict_types=1);

class FileStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Import $import,
        private readonly DriveFile $driveFile,
        private readonly ?AdditionalMetaDto $meta = null
    ) {}

    public function handle(FileStoreService $storeService): void
    {
        $storeService->save($this->import, $this->driveFile, $this->meta);
    }
}
