<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClubChangeRequestsExport
{
    public function download(Collection $requests): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('أرشيف طلبات التغيير');

        $statusLabels = [
            'pending'        => 'معلّق',
            'approved'       => 'مقبول',
            'rejected'       => 'مرفوض',
            'auto_cancelled' => 'ملغى',
        ];

        $headers = [
            'اسم الوكيل',
            'الموزّع',
            'نوع التغيير',
            'من',
            'إلى',
            'الحالة',
            'راجعه',
            'تاريخ المراجعة',
            'الملاحظة',
            'تاريخ الإنشاء',
        ];

        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . '1', $header);
        }

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($requests as $request) {
            $sheet->setCellValue('A' . $row, $request->agent?->agent_name ?? '—');
            $sheet->setCellValue('B' . $row, $request->agent?->distributor?->name ?? '—');
            $sheet->setCellValue('C' . $row, $request->change_type === 'promotion' ? 'ترقية' : 'تهبيط');
            $sheet->setCellValue('D' . $row, $request->fromClub?->club_name ?? 'خارج الأندية');
            $sheet->setCellValue('E' . $row, $request->toClub?->club_name ?? 'خارج الأندية');
            $sheet->setCellValue('F' . $row, $statusLabels[$request->status] ?? $request->status);
            $sheet->setCellValue('G' . $row, $request->reviewer?->name ?? '—');
            $sheet->setCellValue('H' . $row, $request->reviewed_at?->format('d/m/Y H:i') ?? '—');
            $sheet->setCellValue('I' . $row, $request->rejection_reason ?: ($request->approval_note ?: '—'));
            $sheet->setCellValue('J' . $row, $request->created_at?->format('d/m/Y H:i') ?? '—');

            $row++;
        }

        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'أرشيف_طلبات_التغيير_' . now()->format('Y-m-d') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
