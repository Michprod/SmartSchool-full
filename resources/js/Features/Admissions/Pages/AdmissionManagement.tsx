import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { Application } from '../types';
import ApplicationForm from '../Components/ApplicationForm';
import type { ApplicationFormData } from '../Components/ApplicationForm';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './AdmissionManagement.css';

const AdmissionManagement: React.FC = () => {
  const [applications, setApplications] = useState<Application[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'all' | 'submitted' | 'under_review' | 'accepted' | 'rejected'>('all');
  const [showApplicationForm, setShowApplicationForm] = useState(false);
  const [editingApplication, setEditingApplication] = useState<ApplicationFormData | undefined>(undefined);

  useEffect(() => {
    const loadApplications = async () => {
      setTimeout(() => {
        const mockApplications: Application[] = [
          {
            id: '1',
            studentInfo: {
              firstName: 'Pierre',
              lastName: 'Nkomo',
              dateOfBirth: new Date('2010-03-15'),
              gender: 'M'
            },
            parentInfo: {
              firstName: 'Joseph',
              lastName: 'Nkomo',
              email: 'joseph.nkomo@email.com',
              phone: '+243901234567'
            },
            documents: [
              { type: 'Acte de naissance', url: '/docs/birth_cert_1.pdf', uploadedAt: new Date() },
              { type: 'Bulletin scolaire', url: '/docs/report_1.pdf', uploadedAt: new Date() }
            ],
            status: 'submitted',
            appliedClass: '6ème A',
            submittedAt: new Date('2024-08-20'),
          },
          {
            id: '2',
            studentInfo: {
              firstName: 'Grace',
              lastName: 'Mbuyi',
              dateOfBirth: new Date('2011-07-08'),
              gender: 'F'
            },
            parentInfo: {
              firstName: 'Marie',
              lastName: 'Mbuyi',
              email: 'marie.mbuyi@email.com',
              phone: '+243912345678'
            },
            documents: [
              { type: 'Acte de naissance', url: '/docs/birth_cert_2.pdf', uploadedAt: new Date() },
              { type: 'Bulletin scolaire', url: '/docs/report_2.pdf', uploadedAt: new Date() },
              { type: 'Photo d\'identité', url: '/docs/photo_2.jpg', uploadedAt: new Date() }
            ],
            status: 'under_review',
            appliedClass: '5ème B',
            submittedAt: new Date('2024-08-18'),
            reviewedAt: new Date('2024-08-19'),
            reviewedBy: 'admin@smartschool.cd'
          },
          {
            id: '3',
            studentInfo: {
              firstName: 'David',
              lastName: 'Kasongo',
              dateOfBirth: new Date('2009-11-25'),
              gender: 'M'
            },
            parentInfo: {
              firstName: 'Paul',
              lastName: 'Kasongo',
              email: 'paul.kasongo@email.com',
              phone: '+243923456789'
            },
            documents: [
              { type: 'Acte de naissance', url: '/docs/birth_cert_3.pdf', uploadedAt: new Date() },
              { type: 'Bulletin scolaire', url: '/docs/report_3.pdf', uploadedAt: new Date() }
            ],
            status: 'accepted',
            appliedClass: '7ème A',
            submittedAt: new Date('2024-08-15'),
            reviewedAt: new Date('2024-08-17'),
            reviewedBy: 'admin@smartschool.cd',
            notes: 'Excellent dossier académique. Admission approuvée.'
          }
        ];
        setApplications(mockApplications);
        setLoading(false);
      }, 1000);
    };

    loadApplications();
  }, []);

  const filteredApplications = applications.filter(app => {
    if (activeTab === 'all') return true;
    return app.status === activeTab;
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'submitted': return 'status-submitted';
      case 'under_review': return 'status-review';
      case 'accepted': return 'status-accepted';
      case 'rejected': return 'status-rejected';
      default: return 'status-submitted';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'submitted': return 'Soumise';
      case 'under_review': return 'En révision';
      case 'accepted': return 'Acceptée';
      case 'rejected': return 'Refusée';
      default: return 'Soumise';
    }
  };

  const getTabCount = (status: string) => {
    if (status === 'all') return applications.length;
    return applications.filter(app => app.status === status).length;
  };

  const handleStatusChange = (applicationId: string, newStatus: string) => {
    setApplications(prev => prev.map(app => 
      app.id === applicationId 
        ? { 
            ...app, 
            status: newStatus as any,
            reviewedAt: new Date(),
            reviewedBy: 'admin@smartschool.cd'
          }
        : app
    ));
  };

  const handleAddApplication = () => {
    setEditingApplication(undefined);
    setShowApplicationForm(true);
  };

  const handleSubmitApplication = (data: ApplicationFormData) => {
    if (editingApplication) {
      console.log('Updating application:', data);
    } else {
      console.log('Adding new application:', data);
    }
    setShowApplicationForm(false);
    setEditingApplication(undefined);
  };

  const handleCancelApplicationForm = () => {
    setShowApplicationForm(false);
    setEditingApplication(undefined);
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement des candidatures...</p>
      </div>
    );
  }

  return (
    <div className="admission-management">
      <Head title="Gestion des Admissions" />
      <div className="page-header">
        <div>
          <h1>Gestion des Admissions</h1>
          <p className="page-subtitle">
            Portail d'inscription 100% en ligne - Suivi des candidatures
          </p>
        </div>
        <button className="btn btn-primary" onClick={handleAddApplication}>
          <span>➕</span>
          Nouvelle Demande
        </button>
      </div>

      {/* Statistiques des admissions */}
      <div className="admission-stats">
        <div className="stat-card total">
          <span className="stat-icon">📝</span>
          <div className="stat-content">
            <h3>Total</h3>
            <span className="stat-number">{applications.length}</span>
            <span className="stat-label">Candidatures</span>
          </div>
        </div>
        
        <div className="stat-card pending">
          <span className="stat-icon">⏳</span>
          <div className="stat-content">
            <h3>En attente</h3>
            <span className="stat-number">{getTabCount('submitted')}</span>
            <span className="stat-label">À traiter</span>
          </div>
        </div>
        
        <div className="stat-card review">
          <span className="stat-icon">🔍</span>
          <div className="stat-content">
            <h3>En révision</h3>
            <span className="stat-number">{getTabCount('under_review')}</span>
            <span className="stat-label">En cours</span>
          </div>
        </div>
        
        <div className="stat-card accepted">
          <span className="stat-icon">✅</span>
          <div className="stat-content">
            <h3>Acceptées</h3>
            <span className="stat-number">{getTabCount('accepted')}</span>
            <span className="stat-label">Validées</span>
          </div>
        </div>
      </div>

      {/* Navigation par onglets */}
      <div className="tabs-navigation">
        <button 
          className={`tab-btn ${activeTab === 'all' ? 'active' : ''}`}
          onClick={() => setActiveTab('all')}
        >
          Toutes ({getTabCount('all')})
        </button>
        <button 
          className={`tab-btn ${activeTab === 'submitted' ? 'active' : ''}`}
          onClick={() => setActiveTab('submitted')}
        >
          Soumises ({getTabCount('submitted')})
        </button>
        <button 
          className={`tab-btn ${activeTab === 'under_review' ? 'active' : ''}`}
          onClick={() => setActiveTab('under_review')}
        >
          En révision ({getTabCount('under_review')})
        </button>
        <button 
          className={`tab-btn ${activeTab === 'accepted' ? 'active' : ''}`}
          onClick={() => setActiveTab('accepted')}
        >
          Acceptées ({getTabCount('accepted')})
        </button>
        <button 
          className={`tab-btn ${activeTab === 'rejected' ? 'active' : ''}`}
          onClick={() => setActiveTab('rejected')}
        >
          Refusées ({getTabCount('rejected')})
        </button>
      </div>

      {/* Liste des candidatures */}
      <div className="applications-list">
        {filteredApplications.map(application => (
          <div key={application.id} className="application-card">
            <div className="application-header">
              <div className="applicant-info">
                <div className="applicant-avatar">
                  {application.studentInfo.firstName.charAt(0)}
                  {application.studentInfo.lastName.charAt(0)}
                </div>
                <div className="applicant-details">
                  <h3 className="applicant-name">
                    {application.studentInfo.firstName} {application.studentInfo.lastName}
                  </h3>
                  <p className="applied-class">Candidature pour: {application.appliedClass}</p>
                  <p className="submission-date">
                    Soumise le {application.submittedAt.toLocaleDateString()}
                  </p>
                </div>
              </div>
              
              <div className="application-status">
                <span className={`status-badge ${getStatusColor(application.status)}`}>
                  {getStatusLabel(application.status)}
                </span>
              </div>
            </div>
            
            <div className="application-content">
              <div className="student-details-section">
                <h4>Informations de l'élève</h4>
                <div className="info-grid">
                  <div className="info-item">
                    <span className="info-label">Date de naissance:</span>
                    <span className="info-value">{application.studentInfo.dateOfBirth.toLocaleDateString()}</span>
                  </div>
                  <div className="info-item">
                    <span className="info-label">Genre:</span>
                    <span className="info-value">{application.studentInfo.gender === 'M' ? 'Masculin' : 'Féminin'}</span>
                  </div>
                </div>
              </div>
              
              <div className="parent-details-section">
                <h4>Contact parent/tuteur</h4>
                <div className="info-grid">
                  <div className="info-item">
                    <span className="info-label">Nom:</span>
                    <span className="info-value">{application.parentInfo.firstName} {application.parentInfo.lastName}</span>
                  </div>
                  <div className="info-item">
                    <span className="info-label">Email:</span>
                    <span className="info-value">{application.parentInfo.email}</span>
                  </div>
                  <div className="info-item">
                    <span className="info-label">Téléphone:</span>
                    <span className="info-value">{application.parentInfo.phone}</span>
                  </div>
                </div>
              </div>
              
              <div className="documents-section">
                <h4>Documents ({application.documents.length})</h4>
                <div className="documents-list">
                  {application.documents.map((doc, index) => (
                    <div key={index} className="document-item">
                      <span className="doc-icon">📄</span>
                      <span className="doc-name">{doc.type}</span>
                      <button className="doc-view-btn">Voir</button>
                    </div>
                  ))}
                </div>
              </div>
              
              {application.notes && (
                <div className="notes-section">
                  <h4>Notes de révision</h4>
                  <p className="review-notes">{application.notes}</p>
                </div>
              )}
            </div>
            
            <div className="application-actions">
              {application.status === 'submitted' && (
                <>
                  <button 
                    className="action-btn primary"
                    onClick={() => handleStatusChange(application.id, 'under_review')}
                  >
                    <span>🔍</span>
                    Commencer la révision
                  </button>
                </>
              )}
              
              {application.status === 'under_review' && (
                <>
                  <button 
                    className="action-btn success"
                    onClick={() => handleStatusChange(application.id, 'accepted')}
                  >
                    <span>✅</span>
                    Accepter
                  </button>
                  <button 
                    className="action-btn danger"
                    onClick={() => handleStatusChange(application.id, 'rejected')}
                  >
                    <span>❌</span>
                    Refuser
                  </button>
                </>
              )}
              
              <button className="action-btn secondary">
                <span>💬</span>
                Contacter parent
              </button>
              
              <button className="action-btn secondary">
                <span>📝</span>
                Ajouter note
              </button>
            </div>
          </div>
        ))}
      </div>

      {filteredApplications.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">📝</div>
          <h3>Aucune candidature</h3>
          <p>Aucune candidature ne correspond au filtre sélectionné.</p>
        </div>
      )}

      {/* Application Form Modal */}
      {showApplicationForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <ApplicationForm
              initialData={editingApplication}
              onSubmit={handleSubmitApplication}
              onCancel={handleCancelApplicationForm}
              mode={editingApplication ? 'edit' : 'create'}
            />
          </div>
        </div>
      )}
    </div>
  );
};

(AdmissionManagement as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default AdmissionManagement;