<?php

require_once __DIR__ . '/../config/notion.php';

date_default_timezone_set('Asia/Jakarta');


function getChecklist()
{
    $url =
        "https://api.notion.com/v1/databases/"
        . DATABASE_ID_CHECKLIST
        . "/query";


    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];


    $items = [];

    $has_more = true;
    $start_cursor = null;


    while ($has_more) {

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);


        $payload = [
            "page_size" => 100,

            "sorts" => [
                [
                    "property" => "Tanggal Cek",
                    "direction" => "descending"
                ]
            ]
        ];


        if ($start_cursor !== null) {
            $payload["start_cursor"] = $start_cursor;
        }


        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );


        $response = curl_exec($ch);


        if (curl_errno($ch)) {

            $error = curl_error($ch);

            curl_close($ch);

            return [
                'error' => $error
            ];
        }


        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );


        curl_close($ch);


        if ($httpCode !== 200) {

            return [
                'error' =>
                    "HTTP Error: {$httpCode}",

                'raw' =>
                    $response
            ];
        }


        $data =
            json_decode(
                $response,
                true
            );


        if (
            !$data ||
            !isset($data['results'])
        ) {

            return [
                'error' =>
                    'Invalid API response',

                'raw' =>
                    $response
            ];
        }


        /* =====================================================
         * LOOP CHECKLIST
         * =================================================== */

        foreach ($data['results'] as $item) {

            $props =
                $item['properties']
                ?? [];


            $id =
                strtoupper(
                    substr(
                        $item['id'],
                        0,
                        6
                    )
                );


            /* =================================================
             * CHECKLIST
             * ================================================= */

            $checklistArr =
                $props['Checklist']['multi_select']
                ?? [];


            $checklistType =
                !empty($checklistArr)
                    ? ($checklistArr[0]['name'] ?? '-')
                    : '-';


            /* =================================================
             * TANGGAL
             * ================================================= */

            $tanggalRaw =
                $props['Tanggal Cek']['date']['start']
                ?? null;


            $tanggal =
                $tanggalRaw
                    ? date(
                        'd M Y',
                        strtotime($tanggalRaw)
                    )
                    : '-';


            /* =================================================
             * CHECKBOX
             * ================================================= */

            $pro1 =
                $props['Pro 1']['checkbox']
                ?? false;


            $pro2 =
                $props['Pro 2']['checkbox']
                ?? false;


            $pro4 =
                $props['Pro 4']['checkbox']
                ?? false;


            $tetrack =
                $props['Checkbox']['checkbox']
                ?? false;


            $mcr =
                $props['MCR Depan']['checkbox']
                ?? false;


            /* =================================================
             * PETUGAS
             * ================================================= */

            $petugasOptions =
                $props['Petugas DP']['multi_select']
                ?? [];


            $petugasArr =
                array_map(
                    fn($p) =>
                        $p['name'] ?? '',
                    $petugasOptions
                );


            $petugasArr =
                array_values(
                    array_filter(
                        $petugasArr
                    )
                );


            $petugas =
                !empty($petugasArr)
                    ? implode(
                        ', ',
                        $petugasArr
                    )
                    : 'Belum Ditentukan';


            /* =================================================
             * KETERANGAN
             * ================================================= */

            $keterangan = '';


            foreach (
                (
                    $props['Keterangan Checklist']['rich_text']
                    ?? []
                ) as $rt
            ) {

                $keterangan .=
                    $rt['plain_text']
                    ?? '';
            }


            /* =================================================
             * SUBMISSION TIME
             * ================================================= */

            $submissionRaw =
                $props['Submission time']['created_time']
                ?? $item['created_time']
                ?? null;


            if ($submissionRaw) {

                $submissionDate =
                    new DateTime(
                        $submissionRaw,
                        new DateTimeZone('UTC')
                    );


                $submissionDate->setTimezone(
                    new DateTimeZone('Asia/Jakarta')
                );


                $submission =
                    $submissionDate->format(
                        'd M Y, H:i'
                    ) . ' WIB';

            } else {

                $submission = '-';
            }


            /* =================================================
             * RESPONDENT
             * ================================================= */

            $respondentName =
                $props['Respondent']['created_by']['name']
                ?? 'Unknown';


                    /* =================================================
             * PDF DARI NOTION
             * Property: PDF
             * Type: Files & media
             * ================================================= */

            $pdfFiles = [];
            $pdfUrl = '';

            /*
             * Ambil property PDF.
             */
            $pdfProperty = $props['PDF'] ?? null;

            /*
             * DEBUG:
             * sementara bisa lihat di error log server
             */
            error_log(
                '[CHECKLIST] PDF PROPERTY: ' .
                print_r($pdfProperty, true)
            );

            /*
             * Pastikan property mempunyai files.
             */
            if (
                is_array($pdfProperty) &&
                isset($pdfProperty['files']) &&
                is_array($pdfProperty['files'])
            ) {

                foreach ($pdfProperty['files'] as $file) {

                    $fileName = $file['name'] ?? 'Dokumen PDF';
                    $fileUrl = '';

                    /*
                     * Upload langsung ke Notion
                     */
                    if (
                        isset($file['file']) &&
                        is_array($file['file']) &&
                        !empty($file['file']['url'])
                    ) {

                        $fileUrl = $file['file']['url'];
                    }

                    /*
                     * External URL
                     */
                    elseif (
                        isset($file['external']) &&
                        is_array($file['external']) &&
                        !empty($file['external']['url'])
                    ) {

                        $fileUrl = $file['external']['url'];
                    }

                    /*
                     * Simpan hanya kalau URL ditemukan.
                     */
                    if (!empty($fileUrl)) {

                        $pdfFiles[] = [
                            'name' => $fileName,
                            'url' => $fileUrl,
                            'type' => 'pdf',
                            'expiry_time' =>
                                $file['file']['expiry_time'] ?? null
                        ];
                    }
                }
            }

            /*
             * Ambil PDF pertama sebagai URL utama.
             */
            if (!empty($pdfFiles)) {
                $pdfUrl = $pdfFiles[0]['url'] ?? '';
            }

            error_log(
                '[CHECKLIST] PDF URL: ' . $pdfUrl
            );



            /* =================================================
             * CHECKLIST COMPLETION
             * ================================================= */

            $totalItems = 5;


            $doneItems =
                ($pro1 ? 1 : 0) +
                ($pro2 ? 1 : 0) +
                ($pro4 ? 1 : 0) +
                ($tetrack ? 1 : 0) +
                ($mcr ? 1 : 0);


            $isComplete =
                $doneItems === $totalItems;


            /* =================================================
             * RESULT
             * ================================================= */

            $items[] = [

                'id' =>
                    $id,


                'page_id' =>
                    $item['id'],


                'url' =>
                    $item['url']
                    ?? '#',


                'checklist_type' =>
                    $checklistType,


                'tanggal_raw' =>
                    $tanggalRaw,


                'tanggal' =>
                    $tanggal,


                'pro1' =>
                    $pro1,


                'pro2' =>
                    $pro2,


                'pro4' =>
                    $pro4,


                'tetrack' =>
                    $tetrack,


                'mcr' =>
                    $mcr,


                'petugas' =>
                    $petugas,


                'petugas_arr' =>
                    $petugasArr,


                'keterangan' =>
                    $keterangan,


                'submission' =>
                    $submission,


                'respondent' =>
                    $respondentName,


                /*
                 * PDF
                 */
               
                'pdf' =>
                    $pdfUrl,

                'pdf_files' =>
                    $pdfFiles,

                'pdf_count' =>
                    count($pdfFiles),
                
                'done_items' =>
                    $doneItems,


                'total_items' =>
                    $totalItems,


                'is_complete' =>
                    $isComplete,
            ];
        }


        /* =====================================================
         * PAGINATION
         * =================================================== */

        $has_more =
            $data['has_more']
            ?? false;


        $start_cursor =
            $data['next_cursor']
            ?? null;
    }


    return $items;
}
