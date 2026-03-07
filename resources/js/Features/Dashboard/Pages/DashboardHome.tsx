import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';

interface DashboardStats {
  totalStudents: number;
  totalTeachers: number;
  totalParents: number;
  pendingApplications: number;
  totalRevenue: { cdf: number; usd: number };
  pendingPayments: { cdf: number; usd: number };
  recentActivities: { type: string; description: string; timestamp: Date }[];
}
import './DashboardHome.css';

const DashboardHome: React.FC = () => {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadStats = async () => {
      try {
        const response = await fetch('/api/reports/stats');
        if (!response.ok) throw new Error('Failed to fetch stats');
        const data: DashboardStats = await response.json();
        
        // Convert timestamp strings to Date objects
        data.recentActivities = data.recentActivities.map(activity => ({
          ...activity,
          timestamp: new Date(activity.timestamp)
        }));

        setStats(data);
      } catch (error) {
        console.error('Error loading dashboard stats:', error);
      } finally {
        setLoading(false);
      }
    };

    loadStats();
  }, []);

  const formatCurrency = (amount: number, currency: string) => {
    if (currency === 'CDF') {
      return new Intl.NumberFormat('fr-CD', {
        style: 'currency',
        currency: 'CDF',
        minimumFractionDigits: 0
      }).format(amount);
    } else {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    }
  };

  const getActivityIcon = (type: string) => {
    switch (type) {
      case 'payment': return '💰';
      case 'registration': return '👤';
      case 'notification': return '📢';
      default: return '📝';
    }
  };

  const formatTimeAgo = (date: Date) => {
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / 60000);

    if (diffInMinutes < 1) return 'À l\'instant';
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;
    if (diffInMinutes < 1440) return `Il y a ${Math.floor(diffInMinutes / 60)}h`;
    return `Il y a ${Math.floor(diffInMinutes / 1440)} jour(s)`;
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement du tableau de bord...</p>
      </div>
    );
  }

  if (!stats) {
    return <div className="error-message">Erreur lors du chargement des données</div>;
  }

  return (
    <div className="dashboard-home">
      <Head title="Vue d'ensemble" />
      <div className="page-header">
        <h1>Vue d'ensemble</h1>
        <p className="page-subtitle">
          Tableau de bord principal - smartSchool RDC
        </p>
      </div>

      {/* Statistiques principales */}
      <div className="stats-grid">
        <div className="stat-card students">
          <div className="stat-icon">👥</div>
          <div className="stat-content">
            <h3>Élèves</h3>
            <div className="stat-number">{stats.totalStudents.toLocaleString()}</div>
            <div className="stat-label">Élèves inscrits</div>
          </div>
        </div>

        <div className="stat-card teachers">
          <div className="stat-icon">👨‍🏫</div>
          <div className="stat-content">
            <h3>Enseignants</h3>
            <div className="stat-number">{stats.totalTeachers}</div>
            <div className="stat-label">Professeurs actifs</div>
          </div>
        </div>

        <div className="stat-card parents">
          <div className="stat-icon">👨‍👩‍👧‍👦</div>
          <div className="stat-content">
            <h3>Parents</h3>
            <div className="stat-number">{stats.totalParents.toLocaleString()}</div>
            <div className="stat-label">Comptes parents</div>
          </div>
        </div>

        <div className="stat-card applications">
          <div className="stat-icon">📝</div>
          <div className="stat-content">
            <h3>Inscriptions</h3>
            <div className="stat-number">{stats.pendingApplications}</div>
            <div className="stat-label">En attente</div>
          </div>
        </div>
      </div>

      {/* Statistiques financières */}
      <div className="financial-overview">
        <h2>Aperçu Financier</h2>
        <div className="financial-grid">
          <div className="financial-card revenue">
            <h3>Revenus Totaux</h3>
            <div className="currency-amounts">
              <div className="amount-item">
                <span className="currency">CDF</span>
                <span className="amount">{formatCurrency(stats.totalRevenue.cdf, 'CDF')}</span>
              </div>
              <div className="amount-item">
                <span className="currency">USD</span>
                <span className="amount">{formatCurrency(stats.totalRevenue.usd, 'USD')}</span>
              </div>
            </div>
          </div>

          <div className="financial-card pending">
            <h3>Paiements en Attente</h3>
            <div className="currency-amounts">
              <div className="amount-item">
                <span className="currency">CDF</span>
                <span className="amount">{formatCurrency(stats.pendingPayments.cdf, 'CDF')}</span>
              </div>
              <div className="amount-item">
                <span className="currency">USD</span>
                <span className="amount">{formatCurrency(stats.pendingPayments.usd, 'USD')}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Activités récentes */}
      <div className="recent-activities">
        <h2>Activités Récentes</h2>
        <div className="activities-list">
          {stats.recentActivities.map((activity, index) => (
            <div key={index} className="activity-item">
              <div className="activity-icon">
                {getActivityIcon(activity.type)}
              </div>
              <div className="activity-content">
                <p className="activity-description">{activity.description}</p>
                <span className="activity-time">{formatTimeAgo(activity.timestamp)}</span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Actions rapides */}
      <div className="quick-actions">
        <h2>Actions Rapides</h2>
        <div className="actions-grid">
          <button className="action-btn primary" onClick={() => router.visit('/students')}>
            <span className="action-icon">➕</span>
            <span>Nouvel Élève</span>
          </button>
          <button className="action-btn secondary">
            <span className="action-icon">💰</span>
            <span>Enregistrer Paiement</span>
          </button>
          <button className="action-btn secondary">
            <span className="action-icon">📢</span>
            <span>Envoyer Notification</span>
          </button>
          <button className="action-btn secondary">
            <span className="action-icon">📊</span>
            <span>Générer Rapport</span>
          </button>
        </div>
      </div>
    </div>
  );
};

(DashboardHome as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default DashboardHome;