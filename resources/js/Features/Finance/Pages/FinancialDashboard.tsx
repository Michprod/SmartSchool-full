import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { Payment, Currency } from '../types';
import PaymentForm from '../Components/PaymentForm';
import type { PaymentFormData } from '../Components/PaymentForm';
import PaymentReceipt from '../Components/PaymentReceipt';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './FinancialDashboard.css';
import axios from 'axios';

const FinancialDashboard: React.FC = () => {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'payments' | 'mobile-money'>('overview');
  const [selectedCurrency, setSelectedCurrency] = useState<Currency | 'ALL'>('ALL');
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const [editingPayment, setEditingPayment] = useState<PaymentFormData | undefined>(undefined);
  const [showReceipt, setShowReceipt] = useState(false);
  const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
  const [reportStats, setReportStats] = useState<any>(null);

  const loadStats = async () => {
    try {
      const response = await fetch('/api/reports/stats');
      if (response.ok) {
        const data = await response.json();
        setReportStats(data.finance);
      }
    } catch (e) { console.error('Error loading stats:', e); }
  };

  const loadPayments = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/payments');
      if (!response.ok) throw new Error('Failed to fetch payments');
      const paginatedData = await response.json();
      
      const paymentsWithDates = paginatedData.data.map((p: any) => ({
        ...p,
        id: p.id.toString(),
        studentId: p.student_id ? p.student_id.toString() : '',
        amount: parseFloat(p.amount) || Math.round(Number(p.amount)) || 0,
        currency: p.currency,
        type: p.type,
        status: p.status,
        paymentMethod: p.payment_method,
        mobileMoneyProvider: p.mobile_money_provider,
        transactionId: p.transaction_id,
        dueDate: p.due_date ? new Date(p.due_date) : new Date(),
        paidAt: p.paid_at ? new Date(p.paid_at) : undefined,
        createdAt: p.created_at ? new Date(p.created_at) : new Date(),
        student: p.student
      }));

      setPayments(paymentsWithDates);
      await loadStats();
    } catch (error) {
      console.error('Error loading payments:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadPayments();
  }, []);

  const formatCurrency = (amount: number, currency: Currency) => {
    if (currency === 'CDF') {
      return `${amount.toLocaleString()} CDF`;
    }
    return `$${amount.toLocaleString()}`;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed': return 'status-completed';
      case 'pending': return 'status-pending';
      case 'failed': return 'status-failed';
      default: return 'status-pending';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'completed': return 'Payé';
      case 'pending': return 'En attente';
      case 'failed': return 'Échoué';
      default: return 'En attente';
    }
  };

  const getProviderIcon = (provider?: string) => {
    switch (provider) {
      case 'mpesa': return '📱';
      case 'orange_money': return '🧡';
      case 'airtel_money': return '🔴';
      default: return '💰';
    }
  };

  const [allStudents, setAllStudents] = useState<any[]>([]);

  useEffect(() => {
    const loadStudents = async () => {
      try {
        const response = await fetch('/api/students');
        if (response.ok) {
          const data = await response.json();
          setAllStudents(data.data.map((s: any) => ({
            id: s.id.toString(),
            name: `${s.first_name} ${s.last_name}`,
            class: s.school_class?.name || 'Inconnue',
            photo: s.photo,
            matricule: s.matricule
          })));
        }
      } catch (e) { console.error(e); }
    };
    loadStudents();
  }, []);

  const handleAddPayment = () => {
    setEditingPayment(undefined);
    setShowPaymentForm(true);
  };

  const handleSubmitPayment = async (data: PaymentFormData) => {
    try {
      if (editingPayment && editingPayment.id) {
        console.log('Updating payment:', data);
        const payload = {
          amount: data.amount,
          status: data.status,
          transaction_id: data.transactionReference,
          paid_at: data.status === 'completed' ? data.paymentDate : null,
        };
        await axios.put(`/api/payments/${editingPayment.id}`, payload);
      } else {
        console.log('Creating new payment:', data);
        const payload = {
          student_id: data.studentId,
          amount: data.amount,
          currency: data.currency,
          type: data.paymentType,
          payment_method: data.paymentMethod,
          due_date: data.dueDate || data.paymentDate,
          status: data.status,
          mobile_money_provider: data.mobileMoneyProvider?.toLowerCase().replace('-', '_').replace(' ', '_'),
          transaction_id: data.transactionReference,
          description: data.description,
          paid_at: data.status === 'completed' ? data.paymentDate : null,
        };
        await axios.post('/api/payments', payload);
      }
      await loadPayments();
      setShowPaymentForm(false);
      setEditingPayment(undefined);
    } catch (error: any) {
      console.error('Error submitting payment:', error.response?.data || error.message);
      alert("Erreur lors de l'enregistrement du paiement.");
    }
  };

  const handleCancelPaymentForm = () => {
    setShowPaymentForm(false);
    setEditingPayment(undefined);
  };

  const handleViewReceipt = (payment: Payment) => {
    setSelectedPayment(payment);
    setShowReceipt(true);
  };

  const handleEditPayment = (payment: Payment) => {
    const formData: PaymentFormData = {
      id: payment.id,
      studentId: payment.studentId,
      studentName: `Student ${payment.studentId}`,
      class: '6ème A', // This should come from student data in production
      amount: payment.amount,
      currency: payment.currency,
      paymentType: payment.type,
      paymentMethod: payment.method || payment.paymentMethod,
      paymentDate: payment.paidAt?.toISOString().split('T')[0] || new Date().toISOString().split('T')[0],
      academicYear: `${new Date().getFullYear()}-${new Date().getFullYear() + 1}`,
      description: payment.description || '',
      status: payment.status
    };
    setEditingPayment(formData);
    setShowPaymentForm(true);
  };

  const handleDeletePayment = (paymentId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
      setPayments(prev => prev.filter(p => p.id !== paymentId));
      console.log('Payment deleted:', paymentId);
    }
  };

  const handleExportPayments = () => {
    console.log('Exporting payments to Excel/PDF');
    alert('Export en cours...\n\nLes paiements seront exportés avec:\n- Détails complets\n- Totaux par devise\n- Récapitulatif par méthode de paiement');
  };
  
  // Use handleExportPayments in a button later
  console.log('Export function ready:', handleExportPayments);

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement des données financières...</p>
      </div>
    );
  }

  return (
    <div className="financial-dashboard">
      <Head title="Gestion Financière" />
      <div className="page-header">
        <h1>Gestion Financière</h1>
        <p className="page-subtitle">
          Suivi des paiements et revenus - Support bi-monétaire CDF/USD
        </p>
      </div>

      {/* Navigation par onglets */}
      <div className="tabs-navigation">
        <button 
          className={`tab-btn ${activeTab === 'overview' ? 'active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          <span className="tab-icon">📊</span>
          Vue d'ensemble
        </button>
        <button 
          className={`tab-btn ${activeTab === 'payments' ? 'active' : ''}`}
          onClick={() => setActiveTab('payments')}
        >
          <span className="tab-icon">💳</span>
          Paiements
        </button>
        <button 
          className={`tab-btn ${activeTab === 'mobile-money' ? 'active' : ''}`}
          onClick={() => setActiveTab('mobile-money')}
        >
          <span className="tab-icon">📱</span>
          Mobile Money
        </button>
      </div>

      {/* Vue d'ensemble */}
      {activeTab === 'overview' && (
        <div className="overview-content">
          <div className="revenue-cards">
            <div className="revenue-card completed">
              <div className="card-header">
                <h3>Revenus Encaissés</h3>
                <span className="card-icon">✅</span>
              </div>
              <div className="currency-amounts">
                <div className="amount-row">
                  <span className="currency">CDF</span>
                  <span className="amount">{formatCurrency(reportStats?.totalRevenue?.cdf || 0, 'CDF')}</span>
                </div>
                <div className="amount-row">
                  <span className="currency">USD</span>
                  <span className="amount">{formatCurrency(reportStats?.totalRevenue?.usd || 0, 'USD')}</span>
                </div>
              </div>
            </div>

            <div className="revenue-card pending">
              <div className="card-header">
                <h3>Paiements en Attente</h3>
                <span className="card-icon">⏳</span>
              </div>
              <div className="currency-amounts">
                <div className="amount-row">
                  <span className="currency">CDF</span>
                  <span className="amount">{formatCurrency(reportStats?.pendingPayments?.cdf || 0, 'CDF')}</span>
                </div>
                <div className="amount-row">
                  <span className="currency">USD</span>
                  <span className="amount">{formatCurrency(reportStats?.pendingPayments?.usd || 0, 'USD')}</span>
                </div>
              </div>
            </div>
          </div>

          <div className="mobile-money-summary">
            <h2>Résumé Mobile Money</h2>
            <div className="provider-stats">
              <div className="provider-card">
                <span className="provider-icon">💰</span>
                <div className="provider-info">
                  <h4>M-Pesa</h4>
                  <p>Transactions cumulées</p>
                  <div className="provider-amounts-row">
                    <span className="provider-amount">{formatCurrency(reportStats?.by_method?.mobile_money?.cdf || 0, 'CDF')}</span>
                    <span className="provider-amount usd-amount">{formatCurrency(reportStats?.by_method?.mobile_money?.usd || 0, 'USD')}</span>
                  </div>
                </div>
              </div>
              <div className="provider-card">
                <span className="provider-icon">💵</span>
                <div className="provider-info">
                  <h4>Espèces</h4>
                  <p>Encaissements physiques</p>
                  <div className="provider-amounts-row">
                    <span className="provider-amount">{formatCurrency(reportStats?.by_method?.cash?.cdf || 0, 'CDF')}</span>
                    <span className="provider-amount usd-amount">{formatCurrency(reportStats?.by_method?.cash?.usd || 0, 'USD')}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Liste des paiements */}
      {activeTab === 'payments' && (
        <div className="payments-content">
          <div className="payments-header">
            <div className="currency-filter">
              <label>Devise :</label>
              <select 
                value={selectedCurrency} 
                onChange={(e) => setSelectedCurrency(e.target.value as Currency | 'ALL')}
              >
                <option value="ALL">Toutes</option>
                <option value="CDF">CDF</option>
                <option value="USD">USD</option>
              </select>
            </div>
            <button className="btn btn-primary" onClick={handleAddPayment}>
              <span>➕</span>
              Nouveau Paiement
            </button>
          </div>

          <div className="payments-table">
            <div className="table-header">
              <div className="col-student">Élève</div>
              <div className="col-amount">Montant</div>
              <div className="col-type">Type</div>
              <div className="col-method">Méthode</div>
              <div className="col-status">Statut</div>
              <div className="col-date">Date</div>
              <div className="col-actions">Actions</div>
            </div>
            
            {payments
              .filter(p => selectedCurrency === 'ALL' || p.currency === selectedCurrency)
              .map(payment => (
                <div key={payment.id} className="table-row">
                  <div className="col-student">
                    <div className="student-info">
                      <div className="student-avatar">👤</div>
                      <div>
                        <p className="student-name">{payment.student?.first_name} {payment.student?.last_name}</p>
                        <p className="student-class">{payment.student?.school_class?.name || 'N/A'}</p>
                      </div>
                    </div>
                  </div>
                  <div className="col-amount">
                    <span className="amount-value">
                      {formatCurrency(payment.amount, payment.currency)}
                    </span>
                  </div>
                  <div className="col-type">
                    <span className="payment-type">
                      {payment.type === 'tuition' ? 'Scolarité' : 'Frais'}
                    </span>
                  </div>
                  <div className="col-method">
                    <div className="payment-method">
                      <span className="method-icon">
                        {getProviderIcon(payment.mobileMoneyProvider)}
                      </span>
                      <span>
                        {payment.paymentMethod === 'mobile_money' 
                          ? payment.mobileMoneyProvider?.replace('_', ' ') 
                          : 'Espèces'
                        }
                      </span>
                    </div>
                  </div>
                  <div className="col-status">
                    <span className={`status-badge ${getStatusColor(payment.status)}`}>
                      {getStatusLabel(payment.status)}
                    </span>
                  </div>
                  <div className="col-date">
                    {payment.paidAt 
                      ? payment.paidAt.toLocaleDateString() 
                      : payment.dueDate.toLocaleDateString()
                    }
                  </div>
                  <div className="col-actions">
                    <button className="action-btn" onClick={() => handleViewReceipt(payment)} title="Voir le reçu">
                      🧾
                    </button>
                    <button className="action-btn" onClick={() => handleEditPayment(payment)} title="Modifier">
                      ✏️
                    </button>
                    <button className="action-btn delete" onClick={() => handleDeletePayment(payment.id)} title="Supprimer">
                      🗑️
                    </button>
                  </div>
                </div>
              ))
            }
          </div>
        </div>
      )}

      {/* Mobile Money */}
      {activeTab === 'mobile-money' && (
        <div className="mobile-money-content">
          <div className="integration-status">
            <h2>État des Intégrations Mobile Money</h2>
            <div className="provider-integrations">
              <div className="integration-card active">
                <span className="provider-logo">📱</span>
                <div className="integration-info">
                  <h3>M-Pesa Vodacom</h3>
                  <p className="integration-status-text active">Connecté</p>
                  <p className="last-sync">Dernière sync: Il y a 5 min</p>
                </div>
                <div className="integration-actions">
                  <button className="btn btn-outline">Configurer</button>
                </div>
              </div>
              
              <div className="integration-card active">
                <span className="provider-logo">🧡</span>
                <div className="integration-info">
                  <h3>Orange Money</h3>
                  <p className="integration-status-text active">Connecté</p>
                  <p className="last-sync">Dernière sync: Il y a 2 min</p>
                </div>
                <div className="integration-actions">
                  <button className="btn btn-outline">Configurer</button>
                </div>
              </div>
              
              <div className="integration-card inactive">
                <span className="provider-logo">🔴</span>
                <div className="integration-info">
                  <h3>Airtel Money</h3>
                  <p className="integration-status-text inactive">Non connecté</p>
                  <p className="last-sync">Configuration requise</p>
                </div>
                <div className="integration-actions">
                  <button className="btn btn-primary">Connecter</button>
                </div>
              </div>
            </div>
          </div>

          <div className="qr-code-generator">
            <h2>Générateur de QR Code de Paiement</h2>
            <div className="qr-generator-form">
              <div className="form-row">
                <div className="form-group">
                  <label>Montant</label>
                  <input type="number" placeholder="0" />
                </div>
                <div className="form-group">
                  <label>Devise</label>
                  <select>
                    <option value="CDF">CDF</option>
                    <option value="USD">USD</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Fournisseur</label>
                  <select>
                    <option value="mpesa">M-Pesa</option>
                    <option value="orange_money">Orange Money</option>
                    <option value="airtel_money">Airtel Money</option>
                  </select>
                </div>
              </div>
              <button className="btn btn-primary">
                <span>📱</span>
                Générer QR Code
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Payment Form Modal */}
      {showPaymentForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <PaymentForm
              initialData={editingPayment}
              onSubmit={handleSubmitPayment}
              onCancel={handleCancelPaymentForm}
              mode={editingPayment ? 'edit' : 'create'}
              students={allStudents}
            />
          </div>
        </div>
      )}

      {/* Payment Receipt Modal */}
      {showReceipt && selectedPayment && (
        <PaymentReceipt
          receipt={{
            id: selectedPayment.id,
            receiptNumber: `REC-${selectedPayment.id.slice(0, 8).toUpperCase()}`,
            date: selectedPayment.paidAt || selectedPayment.createdAt,
            studentName: selectedPayment.student ? `${selectedPayment.student.first_name} ${selectedPayment.student.last_name}` : `Élève ${selectedPayment.studentId}`,
            studentId: selectedPayment.student?.matricule || selectedPayment.studentId,
            studentClass: selectedPayment.student?.school_class?.name || 'N/A',
            paymentType: selectedPayment.type === 'tuition' ? 'Scolarité' : 'Frais divers',
            amount: selectedPayment.amount,
            currency: selectedPayment.currency,
            paymentMethod: selectedPayment.mobileMoneyProvider 
              ? `Mobile Money (${selectedPayment.mobileMoneyProvider})` 
              : selectedPayment.paymentMethod === 'cash' ? 'Espèces' : 'Virement',
            transactionRef: selectedPayment.reference || selectedPayment.transactionId,
            schoolYear: `${new Date().getFullYear()}-${new Date().getFullYear() + 1}`,
            notes: selectedPayment.description
          }}
          onClose={() => setShowReceipt(false)}
        />
      )}
    </div>
  );
};

(FinancialDashboard as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default FinancialDashboard;
