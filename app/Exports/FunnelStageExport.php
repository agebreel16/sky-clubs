<?php

namespace App\Exports;

use App\Models\Agent;
use App\Models\ClubChangeRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FunnelStageExport
{
    public function __construct(private string $stage) {}

    public function download(): StreamedResponse
    {
        $pendingIds = ClubChangeRequest::where('status', 'pending')
            ->where('change_type', 'promotion')
            ->pluck('agent_id');

        $query = Agent::with('distributor')
            ->whereNull('current_club_id')
            ->where('is_violator', false)
            ->whereNotIn('agent_id', $pendingIds);

        match ($this->stage) {
            'not_started' => $query->where('transfer_count', '=', 0),
            'in_progress' => $query->whereBetween('transfer_count', [1, 9]),
            'near_door'   => $query->where('transfer_count', '>=', 10),
            default       => null,
        };

        $label = match ($this->stage) {
            'not_started' => 'لم يبدأ بعد',
            'in_progress' => 'في الطريق',
            'near_door'   => 'على الأعتاب',
            default       => 'وكلاء',
        };

        $agents = $query->orderBy('agent_name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle($label);

        $headers = ['اسم الوكيل', 'الهاتف', 'الموزع', 'خطوط التحويل', 'خطوط جديدة', 'إجمالي الزيادة'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($columns[$i] . '1', $header);
        }

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($agents as $agent) {
            $sheet->setCellValue('A' . $row, $agent->agent_name);
            $sheet->setCellValue('B' . $row, $agent->phone ?? '—');
            $sheet->setCellValue('C' . $row, $agent->distributor?->name ?? '—');
            $sheet->setCellValue('D' . $row, $agent->transfer_count);
            $sheet->setCellValue('E' . $row, $agent->new_line_count);
            $sheet->setCellValue('F' . $row, $agent->campaign_increase);
            $row++;
        }

        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = $label . '_' . now()->format('Y-m-d') . '.xlsx';

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
