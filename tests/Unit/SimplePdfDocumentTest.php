<?php

namespace Tests\Unit;

use App\Support\SimplePdfDocument;
use PHPUnit\Framework\TestCase;

class SimplePdfDocumentTest extends TestCase
{
    public function test_output_produces_valid_pdf_structure(): void
    {
        $pdf = new SimplePdfDocument;
        $pdf->addTitle('SmartSchool RDC');
        $pdf->addLine('Eleve : Test Eleve');
        $pdf->addLine('Moyenne generale : 14.50/20', 12, true);

        $content = $pdf->output();

        $this->assertStringStartsWith('%PDF-1.4', $content);
        $this->assertStringContainsString('startxref', $content);
        $this->assertStringContainsString('%%EOF', $content);

        preg_match('/startxref\n(\d+)/', $content, $matches);
        $this->assertNotEmpty($matches[1]);
        $startxrefPos = (int) $matches[1];
        $this->assertSame('xref', substr($content, $startxrefPos, 4));
    }
}
