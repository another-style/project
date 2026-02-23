<?php

namespace App\Services;

use App\Models\CommentImage;
use Illuminate\Http\UploadedFile;

class CommentImageService
{
    /**
     * Глубина вложенности директорий (первые N символов имени файла).
     */
    private const DEPTH = 3;

    /**
     * Базовая директория внутри storage/app/public/.
     */
    private const BASE_DIR = 'comment-images';

    /**
     * Сохраняет загруженное изображение на диск и возвращает модель CommentImage.
     *
     * Имя файла формируется как md5-хеш содержимого файла + расширение.
     * Директория строится по принципу из DirectoryGenerator: первые DEPTH символов
     * имени файла образуют вложенные поддиректории.
     *
     * Если файл с таким именем уже существует в БД, возвращается существующая запись.
     */
    public function store(UploadedFile $file): CommentImage
    {
        $md5 = md5_file($file->getRealPath());
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $md5 . '.' . $extension;

        $dir = $this->buildDirectory($filename);

        // Если файла ещё нет на диске — сохраняем
        if (!file_exists($dir . $filename)) {
            $file->move($dir, $filename);
        }

        return CommentImage::firstOrCreate(['filename' => $filename]);
    }

    /**
     * Строит путь к директории назначения, создавая её при необходимости.
     *
     * Принцип взят из DirectoryGenerator: для глубины DEPTH берёт первые DEPTH
     * символов имени файла и создаёт соответствующие вложенные поддиректории.
     * Например, для filename = "d2d8f9c2...jpg" и DEPTH = 3: base/d/2/d/
     *
     * @return string Полный путь к директории (с завершающим разделителем)
     */
    private function buildDirectory(string $filename): string
    {
        $dir = storage_path('app/public/' . self::BASE_DIR);

        // Создаём базовую директорию, если она ещё не существует
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        for ($i = 0; $i < self::DEPTH; $i++) {
            $dir .= DIRECTORY_SEPARATOR . $filename[$i];
            if (!is_dir($dir)) {
                mkdir($dir, 0775);
            }
        }

        return $dir . DIRECTORY_SEPARATOR;
    }
}
