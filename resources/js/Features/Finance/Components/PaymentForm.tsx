import React, { useState } from 'react';
import type { FormEvent } from 'react';
import './PaymentForm.css';

export interface PaymentFormData {
  id?: string;
  studentId: string;
  studentName: string;
  class: string;
  
  // Payment Details
  paymentType: 'tuition' | 'registration' | 'exam' | 'uniform' | 'transport' | 'meal' | 'other';
  description: string;
  amount: number;
  currency: 'CDF' | 'USD';
  
  // Payment Method
  paymentMethod: 'cash' | 'bank_transfer' | 'mobile_money' | 'check';
  mobileMoneyProvider?: 'Airtel Money' | 'Orange Money' | 'M-Pesa' | 'Afrimoney';
  transactionReference?: string;
  
  // Date Information
  paymentDate: string;
  dueDate?: string;
  academicYear: string;
  
  // Status
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  receiptNumber?: string;
  
  // Additional
  notes?: string;
  processedBy?: string;
}

interface PaymentFormProps {
  initialData?: PaymentFormData;
  onSubmit: (data: PaymentFormData) => void;
  onCancel: () => void;
  mode?: 'create' | 'edit';
  students?: Array<{ id: string; name: string; class: string; photo?: string; matricule?: string }>;
}

const PaymentForm: React.FC<PaymentFormProps> = ({
  initialData,
  onSubmit,
  onCancel,
  mode = 'create',
  students = []
}) => {
  const [formData, setFormData] = useState<PaymentFormData>(initialData || {
    studentId: '',
    studentName: '',
    class: '',
    paymentType: 'tuition',
    description: '',
    amount: 0,
    currency: 'CDF',
    paymentMethod: 'cash',
    paymentDate: new Date().toISOString().split('T')[0],
    academicYear: new Date().getFullYear().toString(),
    status: 'completed'
  });

  const [errors, setErrors] = useState<Partial<Record<keyof PaymentFormData, string>>>({});
  const [exchangeRate] = useState(2800); // CDF to USD rate

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    
    setFormData(prev => {
      const updated = { ...prev, [name]: value };
      
      // Auto-populate student info when studentId changes
      if (name === 'studentId' && students.length > 0) {
        const student = students.find(s => s.id === value);
        if (student) {
          updated.studentName = student.name;
          updated.class = student.class;
        }
      }
      
      return updated;
    });
    
    if (errors[name as keyof PaymentFormData]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Partial<Record<keyof PaymentFormData, string>> = {};

    if (!formData.studentId) newErrors.studentId = 'Étudiant requis';
    if (!formData.description) newErrors.description = 'Description requise';
    if (!formData.amount || formData.amount <= 0) newErrors.amount = 'Montant invalide';
    if (!formData.paymentDate) newErrors.paymentDate = 'Date requise';
    
    if (formData.paymentMethod === 'mobile_money' && !formData.mobileMoneyProvider) {
      newErrors.mobileMoneyProvider = 'Fournisseur requis';
    }
    
    if (['bank_transfer', 'mobile_money'].includes(formData.paymentMethod) && !formData.transactionReference) {
      newErrors.transactionReference = 'Référence requise';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (validateForm()) {
      onSubmit(formData);
    }
  };

  const getAmountInOtherCurrency = () => {
    if (formData.currency === 'CDF') {
      return (formData.amount / exchangeRate).toFixed(2) + ' USD';
    }
    return (formData.amount * exchangeRate).toLocaleString() + ' CDF';
  };

  return (
    <div className="payment-form-container">
      <div className="payment-form-header">
        <h2>{mode === 'create' ? '💰 Nouveau Paiement' : '✏️ Modifier Paiement'}</h2>
      </div>

      <form onSubmit={handleSubmit} className="payment-form">
        {/* Student Selection */}
        <div className="form-section">
          <h3>👨‍🎓 Étudiant</h3>
          
          <div className="form-row">
            <div className="form-group">
              <label>Sélectionner l'Étudiant *</label>
              <select
                name="studentId"
                value={formData.studentId}
                onChange={handleChange}
                className={errors.studentId ? 'error' : ''}
                disabled={mode === 'edit'}
              >
                <option value="">-- Choisir un étudiant --</option>
                {students.map(student => (
                  <option key={student.id} value={student.id}>
                    {student.name} - {student.class}
                  </option>
                ))}
              </select>
              {errors.studentId && <span className="error-message">{errors.studentId}</span>}
            </div>

            {/* Student Preview Section */}
            {formData.studentId && (
              <div className="student-preview-card">
                <div className="student-preview-avatar">
                  {students.find(s => s.id === formData.studentId)?.photo ? (
                    <img 
                      src={students.find(s => s.id === formData.studentId)?.photo?.startsWith('data:') 
                        ? students.find(s => s.id === formData.studentId)?.photo 
                        : `/storage/${students.find(s => s.id === formData.studentId)?.photo}`
                      } 
                      alt="Student" 
                    />
                  ) : (
                    <div className="avatar-placeholder">👤</div>
                  )}
                </div>
                <div className="student-preview-info">
                  <h4>{formData.studentName}</h4>
                  <p><strong>Classe:</strong> {formData.class}</p>
                  <p><strong>Matricule:</strong> {students.find(s => s.id === formData.studentId)?.matricule || formData.studentId}</p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Payment Information */}
        <div className="form-section">
          <h3>💳 Informations de Paiement</h3>
          
          <div className="form-row">
            <div className="form-group">
              <label>Type de Paiement *</label>
              <select name="paymentType" value={formData.paymentType} onChange={handleChange}>
                <option value="tuition">Frais Scolaires</option>
                <option value="registration">Frais d'Inscription</option>
                <option value="exam">Frais d'Examen</option>
                <option value="uniform">Uniforme</option>
                <option value="transport">Transport</option>
                <option value="meal">Cantine</option>
                <option value="other">Autre</option>
              </select>
            </div>

            <div className="form-group">
              <label>Année Académique *</label>
              <input
                type="text"
                name="academicYear"
                value={formData.academicYear}
                onChange={handleChange}
                placeholder="2024-2025"
              />
            </div>
          </div>

          <div className="form-group">
            <label>Description *</label>
            <input
              type="text"
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Ex: Frais scolaires - 1er trimestre"
              className={errors.description ? 'error' : ''}
            />
            {errors.description && <span className="error-message">{errors.description}</span>}
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Montant *</label>
              <input
                type="number"
                name="amount"
                value={formData.amount}
                onChange={handleChange}
                min="0"
                step="0.01"
                placeholder="0.00"
                className={errors.amount ? 'error' : ''}
              />
              {errors.amount && <span className="error-message">{errors.amount}</span>}
            </div>

            <div className="form-group">
              <label>Devise *</label>
              <select name="currency" value={formData.currency} onChange={handleChange}>
                <option value="CDF">🇨🇩 Francs Congolais (CDF)</option>
                <option value="USD">🇺🇸 Dollars US (USD)</option>
              </select>
            </div>
          </div>

          {formData.amount > 0 && (
            <div className="conversion-info">
              ≈ {getAmountInOtherCurrency()} (Taux: 1 USD = {exchangeRate} CDF)
            </div>
          )}
        </div>

        {/* Payment Method */}
        <div className="form-section">
          <h3>💸 Méthode de Paiement</h3>
          
          <div className="form-group">
            <label>Méthode *</label>
            <select name="paymentMethod" value={formData.paymentMethod} onChange={handleChange}>
              <option value="cash">💵 Espèces</option>
              <option value="bank_transfer">🏦 Virement Bancaire</option>
              <option value="mobile_money">📱 Mobile Money</option>
              <option value="check">📝 Chèque</option>
            </select>
          </div>

          {formData.paymentMethod === 'mobile_money' && (
            <div className="form-group">
              <label>Fournisseur Mobile Money *</label>
              <select
                name="mobileMoneyProvider"
                value={formData.mobileMoneyProvider || ''}
                onChange={handleChange}
                className={errors.mobileMoneyProvider ? 'error' : ''}
              >
                <option value="">-- Sélectionner --</option>
                <option value="Airtel Money">Airtel Money</option>
                <option value="Orange Money">Orange Money</option>
                <option value="M-Pesa">M-Pesa (Vodacom)</option>
                <option value="Afrimoney">Afrimoney</option>
              </select>
              {errors.mobileMoneyProvider && <span className="error-message">{errors.mobileMoneyProvider}</span>}
            </div>
          )}

          {['bank_transfer', 'mobile_money'].includes(formData.paymentMethod) && (
            <div className="form-group">
              <label>Référence de Transaction *</label>
              <input
                type="text"
                name="transactionReference"
                value={formData.transactionReference || ''}
                onChange={handleChange}
                placeholder="Ex: TXN123456789"
                className={errors.transactionReference ? 'error' : ''}
              />
              {errors.transactionReference && <span className="error-message">{errors.transactionReference}</span>}
            </div>
          )}
        </div>

        {/* Dates and Status */}
        <div className="form-section">
          <h3>📅 Dates et Statut</h3>
          
          <div className="form-row">
            <div className="form-group">
              <label>Date de Paiement *</label>
              <input
                type="date"
                name="paymentDate"
                value={formData.paymentDate}
                onChange={handleChange}
                className={errors.paymentDate ? 'error' : ''}
              />
              {errors.paymentDate && <span className="error-message">{errors.paymentDate}</span>}
            </div>

            <div className="form-group">
              <label>Date d'Échéance</label>
              <input
                type="date"
                name="dueDate"
                value={formData.dueDate || ''}
                onChange={handleChange}
              />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Statut *</label>
              <select name="status" value={formData.status} onChange={handleChange}>
                <option value="pending">⏳ En Attente</option>
                <option value="completed">✅ Complété</option>
                <option value="failed">❌ Échoué</option>
                <option value="refunded">↩️ Remboursé</option>
              </select>
            </div>

            <div className="form-group">
              <label>N° Reçu</label>
              <input
                type="text"
                name="receiptNumber"
                value={formData.receiptNumber || ''}
                onChange={handleChange}
                placeholder="Auto-généré si vide"
              />
            </div>
          </div>
        </div>

        {/* Additional Information */}
        <div className="form-section">
          <h3>📝 Informations Complémentaires</h3>
          
          <div className="form-group">
            <label>Notes</label>
            <textarea
              name="notes"
              value={formData.notes || ''}
              onChange={handleChange}
              placeholder="Remarques ou observations..."
              rows={3}
            />
          </div>

          <div className="form-group">
            <label>Traité Par</label>
            <input
              type="text"
              name="processedBy"
              value={formData.processedBy || ''}
              onChange={handleChange}
              placeholder="Nom de l'agent"
            />
          </div>
        </div>

        <div className="form-actions">
          <button type="button" onClick={onCancel} className="btn-cancel">
            Annuler
          </button>
          <button type="submit" className="btn-success">
            {mode === 'create' ? '✓ Enregistrer le Paiement' : '✓ Mettre à Jour'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default PaymentForm;
