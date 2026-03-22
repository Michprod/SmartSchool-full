import React, { useRef } from 'react';
import './PaymentReceipt.css';

export interface ReceiptData {
  id: string;
  receiptNumber: string;
  date: Date;
  studentName: string;
  studentId: string;
  studentClass: string;
  paymentType: string;
  amount: number;
  currency: 'CDF' | 'USD';
  paymentMethod: string;
  transactionRef?: string;
  schoolYear: string;
  paidBy?: string;
  notes?: string;
}

interface PaymentReceiptProps {
  receipt: ReceiptData;
  onClose: () => void;
  onPrint?: () => void;
}

const PaymentReceipt: React.FC<PaymentReceiptProps> = ({ receipt, onClose, onPrint }) => {
  const receiptRef = useRef<HTMLDivElement>(null);

  const formatCurrency = (amount: number, currency: 'CDF' | 'USD') => {
    return new Intl.NumberFormat('fr-CD', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: currency === 'USD' ? 2 : 0,
    }).format(amount);
  };

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('fr-CD', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const handlePrint = () => {
    window.print();
    onPrint?.();
  };

  const handleDownloadPDF = () => {
    // In production, use a library like jsPDF or html2pdf
    alert('Fonctionnalité PDF en cours de développement');
  };

  return (
    <div className="receipt-modal-overlay">
      <div className="receipt-modal">
        <div className="receipt-container" ref={receiptRef}>
          {/* Header */}
          <div className="receipt-header">
            <div className="school-logo">
              <div className="logo-placeholder">🏫</div>
            </div>
            <div className="school-info">
              <h1>SmartSchool RDC</h1>
              <p>Avenue de la Libération, Kinshasa</p>
              <p>Tél: +243 81 234 5678 | Email: info@smartschool.cd</p>
              <p>RCCM: CD/KIN/RCCM/12-A-12345</p>
            </div>
          </div>

          <div className="receipt-title">
            <h2>REÇU DE PAIEMENT</h2>
            <div className="receipt-number">N° {receipt.receiptNumber}</div>
          </div>

          {/* Receipt Details */}
          <div className="receipt-body">
            <div className="receipt-section">
              <h3>Informations de l'élève</h3>
              <div className="info-grid">
                <div className="info-item">
                  <span className="info-label">Nom complet:</span>
                  <span className="info-value">{receipt.studentName}</span>
                </div>
                <div className="info-item">
                  <span className="info-label">Matricule:</span>
                  <span className="info-value">{receipt.studentId}</span>
                </div>
                <div className="info-item">
                  <span className="info-label">Classe:</span>
                  <span className="info-value">{receipt.studentClass}</span>
                </div>
                <div className="info-item">
                  <span className="info-label">Année scolaire:</span>
                  <span className="info-value">{receipt.schoolYear}</span>
                </div>
              </div>
            </div>

            <div className="receipt-section">
              <h3>Détails du paiement</h3>
              <div className="info-grid">
                <div className="info-item">
                  <span className="info-label">Date:</span>
                  <span className="info-value">{formatDate(receipt.date)}</span>
                </div>
                <div className="info-item">
                  <span className="info-label">Type de paiement:</span>
                  <span className="info-value">{receipt.paymentType}</span>
                </div>
                <div className="info-item">
                  <span className="info-label">Méthode:</span>
                  <span className="info-value">{receipt.paymentMethod}</span>
                </div>
                {receipt.transactionRef && (
                  <div className="info-item">
                    <span className="info-label">Référence:</span>
                    <span className="info-value">{receipt.transactionRef}</span>
                  </div>
                )}
                {receipt.paidBy && (
                  <div className="info-item">
                    <span className="info-label">Payé par:</span>
                    <span className="info-value">{receipt.paidBy}</span>
                  </div>
                )}
              </div>
            </div>

            <div className="receipt-amount">
              <div className="amount-label">Montant payé</div>
              <div className="amount-value">
                {formatCurrency(receipt.amount, receipt.currency)}
              </div>
            </div>

            {receipt.notes && (
              <div className="receipt-notes">
                <strong>Notes:</strong> {receipt.notes}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="receipt-footer">
            <div className="signature-section">
              <div className="signature">
                <p>Signature du parent/tuteur</p>
                <div className="signature-line"></div>
              </div>
              <div className="signature">
                <p>Signature de l'établissement</p>
                <div className="signature-line"></div>
                <div className="stamp-area">
                  <div className="stamp-placeholder">CACHET</div>
                </div>
              </div>
            </div>

            <div className="receipt-disclaimer">
              <p>Ce reçu est généré électroniquement et constitue une preuve de paiement valide.</p>
              <p>Pour toute réclamation, veuillez contacter l'administration dans les 48h.</p>
            </div>

            <div className="receipt-barcode">
              <div className="barcode-placeholder">
                ||||| ||||| ||||| ||||| ||||| |||||
              </div>
              <small>{receipt.id}</small>
            </div>
          </div>
        </div>

        {/* Action Buttons (not printed) */}
        <div className="receipt-actions no-print">
          <button className="btn btn-secondary" onClick={onClose}>
            Fermer
          </button>
          <button className="btn btn-outline" onClick={handleDownloadPDF}>
            <span>⬇️</span> PDF
          </button>
          <button className="btn btn-primary" onClick={handlePrint}>
            <span>🖨️</span> Imprimer
          </button>
        </div>
      </div>
    </div>
  );
};

export default PaymentReceipt;
