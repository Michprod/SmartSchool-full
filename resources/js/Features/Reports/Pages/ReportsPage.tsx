import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './ReportsPage.css';

interface Report {
  id: string;
  name: string;
  description: string;
  category: 'academic' | 'financial' | 'administrative' | 'students';
  icon: string;
}

const ReportsPage: React.FC = () => {
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState('');

  const reports: Report[] = [
    // Academic Reports
    {
      id: 'student-list',
      name: 'Liste des élèves',
      description: 'Liste complète des élèves avec informations détaillées',
      category: 'students',
      icon: '👥'
    },
    {
      id: 'class-roster',
      name: 'Effectif par classe',
      description: 'Nombre d\'élèves par classe et niveau',
      category: 'academic',
      icon: '📚'
    },
    {
      id: 'attendance',
      name: 'Rapport de présence',
      description: 'Taux de présence par classe et par période',
      category: 'academic',
      icon: '✓'
    },
    {
      id: 'grades',
      name: 'Rapport de notes',
      description: 'Résultats académiques par classe et matière',
      category: 'academic',
      icon: '📊'
    },
    
    // Financial Reports
    {
      id: 'payment-summary',
      name: 'Résumé des paiements',
      description: 'Synthèse des paiements reçus (CDF/USD)',
      category: 'financial',
      icon: '💰'
    },
    {
      id: 'payment-detail',
      name: 'Détails des paiements',
      description: 'Liste détaillée de tous les paiements',
      category: 'financial',
      icon: '💵'
    },
    {
      id: 'outstanding',
      name: 'Impayés',
      description: 'Élèves avec paiements en retard',
      category: 'financial',
      icon: '⚠️'
    },
    {
      id: 'mobile-money',
      name: 'Transactions Mobile Money',
      description: 'Rapport des paiements via Mobile Money',
      category: 'financial',
      icon: '📱'
    },
    {
      id: 'revenue',
      name: 'Revenus par période',
      description: 'Analyse des revenus mensuels/trimestriels',
      category: 'financial',
      icon: '📈'
    },
    
    // Administrative Reports
    {
      id: 'admissions',
      name: 'Rapport d\'admissions',
      description: 'Statistiques des nouvelles inscriptions',
      category: 'administrative',
      icon: '📝'
    },
    {
      id: 'staff',
      name: 'Liste du personnel',
      description: 'Personnel enseignant et administratif',
      category: 'administrative',
      icon: '👨‍🏫'
    },
    {
      id: 'events',
      name: 'Calendrier des événements',
      description: 'Événements passés et à venir',
      category: 'administrative',
      icon: '📅'
    },
    
    // Student Reports
    {
      id: 'student-profile',
      name: 'Fiche élève',
      description: 'Dossier complet d\'un élève',
      category: 'students',
      icon: '📋'
    },
    {
      id: 'parent-contacts',
      name: 'Contacts parents',
      description: 'Liste des contacts parents/tuteurs',
      category: 'students',
      icon: '📞'
    },
    {
      id: 'student-demographics',
      name: 'Données démographiques',
      description: 'Répartition par âge, genre, province',
      category: 'students',
      icon: '📊'
    }
  ];

  const categories = [
    { id: 'all', label: 'Tous les rapports', icon: '📑' },
    { id: 'academic', label: 'Académique', icon: '📚' },
    { id: 'financial', label: 'Financier', icon: '💰' },
    { id: 'administrative', label: 'Administratif', icon: '⚙️' },
    { id: 'students', label: 'Élèves', icon: '👥' }
  ];

  const filteredReports = reports.filter(report => {
    const matchesCategory = selectedCategory === 'all' || report.category === selectedCategory;
    const matchesSearch = report.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          report.description.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const handleGenerateReport = (reportId: string) => {
    console.log('Generating report:', reportId);
    alert(`Génération du rapport en cours...\n\nLe rapport sera disponible au format PDF et Excel.`);
  };

  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  React.useEffect(() => {
    const loadStats = async () => {
      try {
        const response = await fetch('/api/reports/stats');
        if (!response.ok) throw new Error('Failed to fetch stats');
        const data = await response.json();
        setStats(data);
      } catch (error) {
        console.error('Error loading stats:', error);
      } finally {
        setLoading(false);
      }
    };
    loadStats();
  }, []);

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Chargement des rapports...</p>
      </div>
    );
  }

  return (
    <div className="reports-page">
      <Head title="Rapports et Statistiques" />
      <div className="page-header">
        <div>
          <h1>📊 Rapports et Statistiques</h1>
          <p className="page-subtitle">Générez des rapports détaillés pour le suivi et l'analyse</p>
        </div>
      </div>

      {/* Search and Filters */}
      <div className="reports-controls">
        <div className="search-box">
          <input
            type="text"
            placeholder="Rechercher un rapport..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
          <span className="search-icon">🔍</span>
        </div>
      </div>

      {/* Category Tabs */}
      <div className="category-tabs">
        {categories.map(cat => (
          <button
            key={cat.id}
            className={`category-tab ${selectedCategory === cat.id ? 'active' : ''}`}
            onClick={() => setSelectedCategory(cat.id)}
          >
            <span className="tab-icon">{cat.icon}</span>
            <span className="tab-label">{cat.label}</span>
            {cat.id !== 'all' && (
              <span className="tab-count">
                {reports.filter(r => r.category === cat.id).length}
              </span>
            )}
          </button>
        ))}
      </div>

      {/* Reports Grid */}
      <div className="reports-grid">
        {filteredReports.map(report => (
          <div key={report.id} className="report-card">
            <div className="report-icon">{report.icon}</div>
            <div className="report-info">
              <h3 className="report-name">{report.name}</h3>
              <p className="report-description">{report.description}</p>
            </div>
            <div className="report-actions">
              <button
                className="btn btn-primary"
                onClick={() => handleGenerateReport(report.id)}
              >
                <span>📄</span>
                Générer
              </button>
              <button className="btn btn-outline">
                <span>📅</span>
                Planifier
              </button>
            </div>
          </div>
        ))}
      </div>

      {filteredReports.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">🔍</div>
          <h3>Aucun rapport trouvé</h3>
          <p>Aucun rapport ne correspond à vos critères de recherche.</p>
        </div>
      )}

      {/* Quick Stats */}
      <div className="quick-stats">
        <h2>Statistiques rapides</h2>
        <div className="stats-grid">
          <div className="stat-card">
            <div className="stat-icon">👥</div>
            <div className="stat-info">
              <span className="stat-value">{stats?.students?.total || 0}</span>
              <span className="stat-label">Élèves actifs</span>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">💰</div>
            <div className="stat-info">
              <span className="stat-value">{stats?.finance?.collection_rate || 0}%</span>
              <span className="stat-label">Taux de paiement</span>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">📚</div>
            <div className="stat-info">
              <span className="stat-value">{stats?.students?.classes_count || 0}</span>
              <span className="stat-label">Classes actives</span>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">👨‍🏫</div>
            <div className="stat-info">
              <span className="stat-value">{stats?.staff?.total || 0}</span>
              <span className="stat-label">Utilisateurs</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

(ReportsPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default ReportsPage;
