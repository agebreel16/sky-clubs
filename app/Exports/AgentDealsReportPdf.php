<?php

namespace App\Exports;

use App\Models\Agent;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentDealsReportPdf
{
    // يجب إرجاع StreamedResponse تحديداً (وليس Response عادي) لأن Livewire
    // لا يتعرّف تلقائياً على تحميل الملفات إلا لهذا النوع أو BinaryFileResponse،
    // وإلا يحاول تحويل بيانات PDF الثنائية إلى JSON فيفشل بخطأ UTF-8.
    public function download(Agent $agent, array $data): StreamedResponse
    {
        $mpdf = $this->makeMpdf();

        $mpdf->SetHTMLFooter('<div style="font-family:cairo; text-align:center; font-size:9px; color:#8a93a3;">صفحة {PAGENO} من {nbpg}</div>');

        $mpdf->WriteHTML(view('pdf.agent-deals-report', [
            'agent' => $agent,
            ...$data,
        ])->render());

        $filename = 'تقرير_' . str_replace(' ', '_', $agent->agent_name) . '_' . now()->format('Y-m-d') . '.pdf';
        $content  = $mpdf->Output($filename, Destination::STRING_RETURN);

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function makeMpdf(): Mpdf
    {
        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        return new Mpdf([
            'format'          => 'A4',
            'default_font'    => 'cairo',
            'directionality'  => 'rtl',
            'margin_left'     => 16,
            'margin_right'    => 16,
            'margin_top'      => 16,
            'margin_bottom'   => 18,
            'margin_footer'   => 8,
            'fontDir'         => [...$fontDirs, storage_path('fonts')],
            'fontdata'        => [
                ...$fontData,
                'cairo' => [
                    'R'          => 'Cairo-Regular.ttf',
                    'B'          => 'Cairo-Bold.ttf',
                    'useOTL'     => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);
    }
}
