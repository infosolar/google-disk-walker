<?php

declare(strict_types=1);


class FileStoreService
{
    public function __construct(private FilePropertyFactory $fileFactory) {}

    public function save(Import $import, DriveFile $driveFile, ?AdditionalMetaDto $meta = null): void
    {
        try {
            if ($driveFile->trashed) {
                File::query()
                    ->where('file_id', '=', $driveFile->getId())
                    ->delete();

                $import->addStatistics('deleted');
                FileRemoveIndexJob::dispatch($driveFile->getId());
                return;
            }

            $fileProperties = $this->fileFactory->create($driveFile, $meta);

            $fieldStatistics = File::query()
                ->where('file_id', $fileProperties['file_id'])
                ->exists() ? 'updated' : 'added';

            File::query()
                ->updateOrCreate([
                    'file_id' => $fileProperties['file_id'],
                ],
                    $fileProperties
                );

            $import->addStatistics($fieldStatistics);

            $file = File::query()
                ->where('file_id', '=', $fileProperties['file_id'])
                ->first();

            FileIndexJob::dispatch($file);
        } catch (\Exception $exception) {
            Log::error(
                sprintf(
                    '[FILE] %s %s \n%s \n%s',
                    $driveFile->getName(),
                    $driveFile->getWebViewLink(),
                    $exception->getMessage(),
                    $exception->getTraceAsString(),
                )
            );
            $import->exceptions()
                ->create([
                    'file_link' => $driveFile->getWebViewLink(),
                    'file_name' => $driveFile->getName(),
                    'meta' => $exception->getTraceAsString(),
                    'message' => $exception->getMessage(),
                ]);
        }
    }
}
