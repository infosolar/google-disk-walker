<?php

declare(strict_types=1);

class DriveService
{
    private Drive $drive;
    const FOLDER_TYPE = 'application/vnd.google-apps.folder';

    public function __construct(private FileSourceFactory $fileSourceFactory)
    {
        $client = new Client([
            'credentials' => Storage::path(config('google.auth_json')),
        ]);
        $client->useApplicationDefaultCredentials();
        $client->addScope(Drive::DRIVE);
        $this->drive = new Drive($client);
    }

    public function walk(Import $import, string $folderId = null, ?AdditionalMetaDto $meta = null): void
    {
        $folderId = $folderId ?? config('google.drive_root_folder_id');

        $q = sprintf(
            '"%s" in parents',
            $folderId,
        );

        $query = [
            'q' => $q,
            'fields' => 'nextPageToken, files(id, contentHints, name, mimeType, thumbnailLink, parents, modifiedTime, createdTime, webViewLink, kind, fileExtension)',
        ];

        $files = [];
        $query['pageToken'] = null;
        do {
            $response = $this->drive->files->listFiles($query);

            foreach ($response->files as $file) {
                $files[] = $file;
            }

            $query['pageToken'] = $response->nextPageToken;
        } while ($query['pageToken'] != null);

        collect($files)->each(function (Drive\DriveFile $driveFile) use ($import, $meta) {
            try {
                match ($driveFile->getMimeType()) {
                    self::FOLDER_TYPE => (function () use ($import, $meta, $driveFile) {
                        if (!$meta) {
                            $meta = new AdditionalMetaDto();
                        }

                        $year = [];
                        preg_match('/[0-9]{4}/', $driveFile->getName(), $year);

                        if (!$meta->year && isset($year[0])) {
                            $meta->year = $year[0];
                        }

                        $meta->source = $this->fileSourceFactory->create($driveFile->getName());

                        $this->walk(import: $import, folderId: $driveFile->getId(), meta: $meta);
                    })(),
                    default => (function () use ($meta, $driveFile, $import) {

                        if ($import->type === ImportType::LAST_UPDATE
                            && Carbon::parse($driveFile->getModifiedTime())
                                ->diffInDays(Carbon::now()) > 15) {
                            return;
                        }

                        FileStoreJob::dispatch($import, $driveFile, $meta);
                    })(),
                };
            } catch (\Exception $exception) {
                Log::error(sprintf('[IMPORT] %s /n%s', $exception->getMessage(), $exception->getTraceAsString()));
            }
        });
    }
}
