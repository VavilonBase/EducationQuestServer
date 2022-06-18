<?php

namespace api\core;

use api\core\Message;
use api\core\Response;

class YandexDisk
{
    public static $token = 'AQAAAAAruxxNAAfQeTzI1cSR2EM9jayH73WmX7s';

    // Создание папки
    public static function createFolder($path)
    {

        // Путь новой директории.
        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/?path=' . urlencode($path));
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . YandexDisk::$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        if (isset($res['error']) && $res['error'] == 'DiskPathPointsToExistentDirectoryError') {
            Response::sendError(Message::$messages['CanNotCreateFolder'], 500);
        }
    }

    public static function loadFile($path, $file, $fileName)
    {
        // Запрашиваем URL для загрузки.
        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/upload?path=' . urlencode($path . $fileName));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . YandexDisk::$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        if (empty($res['error'])) {
            // Если ошибки нет, то отправляем файл на полученный URL.
            $fp = fopen($file, 'r');

            $ch = curl_init($res['href']);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_exec($ch);
            curl_close($ch);
        } else {
            print_r($res['error']);

            Response::sendError(Message::$messages['CanNotLoadFile'], 400);
        }
    }

    public static function delete($path)
    {
        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($path) . '&permanently=true');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . YandexDisk::$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!empty($res['error'])) Response::sendError(Message::$messages['CanNotDeleteFileOrFolder'], 500);
    }

    public static function setPublish($path, $fileName)
    {
        // Указываем url
        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/publish?path=' . urlencode($path . $fileName));
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . YandexDisk::$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        // Если есть ошибки при публикации файла
        if (!empty($res->error)) {
            Response::sendError(Message::$messages['CanNotPublishFile'], 500);
        }
        // Указываем url
        $ch = curl_init($res->href);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . YandexDisk::$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = json_decode(curl_exec($ch));
        curl_close($ch);
        // Если есть ошибки при получении ссылки на опубликованный файл
        if (!empty($res->error)) {
            Response::sendError(Message::$messages['CanNotGetPublicUrl'], 500);
        }

        return $res->file;
    }
}
