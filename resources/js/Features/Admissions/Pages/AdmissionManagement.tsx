import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { Application } from '../types';
import ApplicationForm from '../Components/ApplicationForm';
import type { ApplicationFormData } from '../Components/ApplicationForm';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import Pagination from '../../../Core/Components/Pagination';
import './AdmissionManagement.css';
import axios from 'axios';

const AdmissionManagement: React.FC = () => {
  const [applications, setApplications] = useState<Application[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'all' | 'submitted' | 'under_review' | 'accepted' | 'rejected'>('all');
  const [showApplicationForm, setShowApplicationForm] = useState(false);
  const [editingApplication, setEditingApplication] = useState<ApplicationFormData | undefined>(undefined);

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  useEffect(() => {
    setCurrentPage(1);
  }, [activeTab]);

  useEffect(() => {
    const loadApplications = async () => {
      try {
        const response = await axios.get('/api/admissions');
        const paginatedData = response.data;
        
        const appsWithMapping = paginatedData.data.map((app: any) => ({
          id: app.id,
          studentInfo: {
            firstName: app.student_first_name,
            lastName: app.student_last_name,
            dateOfBirth: new Date(app.student_date_of_birth),
            gender: app.student_gender
          },
          parentInfo: {
            firstName: app.parent_first_name,
            lastName: app.parent_last_name,
            email: app.parent_email,
            phone: app.parent_phone
          },
          documents: app.documents || [],
          status: app.status,
          appliedClass: app.applied_class,
          submittedAt: new Date(app.created_at),
          reviewedAt: app.reviewed_at ? new Date(app.reviewed_at) : undefined,
          reviewedBy: app.reviewer?.email || app.reviewer_id,
          notes: app.notes
        }));

        setApplications(appsWithMapping);
      } catch (error) {
        console.error('Error loading admissions:', error);
      } finally {
        setLoading(false);
      }
    };

    loadApplications();
  }, []);

  const filteredApplications = applications.filter(app => {
    if (activeTab === 'all') return true;
    return app.status === activeTab;
  });

  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentApplications = filteredApplications.slice(indexOfFirstItem, indexOfLastItem);

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

  const handleStatusChange = async (applicationId: string, newStatus: string) => {
    try {
      const response = await axios.put(`/api/admissions/${applicationId}`, { status: newStatus });
      const app = response.data;
      
      setApplications(prev => prev.map(a => 
        a.id === applicationId 
          ? { 
              ...a, 
              status: app.status as any,
              reviewedAt: app.reviewed_at ? new Date(app.reviewed_at) : new Date(),
              reviewedBy: app.reviewer?.email || app.reviewer_id || 'admin@smartschool.cd',
            }
          : a
      ));
    } catch (e) {
      console.error(e);
      alert("Erreur lors du changement de statut.");
    }
  };

  const handleAddApplication = () => {
    setEditingApplication(undefined);
    setShowApplicationForm(true);
  };

  const handleSubmitApplication = async (data: ApplicationFormData) => {
    try {
      if (editingApplication && editingApplication.id) {
        console.log('Updating application:', data);
        const payload = {
          status: data.status === 'pending' ? 'submitted' : data.status,
          notes: data.notes || '',
        };
        const response = await axios.put(`/api/admissions/${editingApplication.id}`, payload);
        const app = response.data;
        
        setApplications(prev => prev.map(a => 
          a.id === app.id
            ? {
                ...a,
                status: app.status,
                notes: app.notes,
                reviewedAt: app.reviewed_at ? new Date(app.reviewed_at) : undefined,
                reviewedBy: app.reviewer?.email || app.reviewer_id,
              }
            : a
        ));
      } else {
        const payload = {
          student_first_name: data.firstName,
          student_last_name: data.lastName,
          student_date_of_birth: data.dateOfBirth,
          student_gender: data.gender,
          parent_first_name: data.parentName.split(' ')[0] || '',
          parent_last_name: data.parentName.split(' ').slice(1).join(' ') || '',
          parent_email: data.parentEmail || 'noemail@example.com',
          parent_phone: data.parentPhone,
          applied_class: data.appliedClass,
          documents: [],
          notes: data.notes || '',
        };
        console.log('Adding new application:', payload);
        const response = await axios.post('/api/admissions', payload);
        const app = response.data;
        
        const newApplication: Application = {
          id: app.id,
          studentInfo: {
            firstName: app.student_first_name,
            lastName: app.student_last_name,
            dateOfBirth: new Date(app.student_date_of_birth),
            gender: app.student_gender
          },
          parentInfo: {
            firstName: app.parent_first_name,
            lastName: app.parent_last_name,
            email: app.parent_email,
            phone: app.parent_phone
          },
          documents: app.documents || [],
          status: app.status,
          appliedClass: app.applied_class,
          submittedAt: new Date(app.created_at)
        };
        setApplications(prev => [newApplication, ...prev]);
      }
      setShowApplicationForm(false);
      setEditingApplication(undefined);
    } catch (error: any) {
      console.error('Error submitting application:', error.response?.data || error.message);
      alert("Erreur lors de l'enregistrement, veuillez vérifier les informations saisies.");
    }
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

      {/* Tableau des candidatures */}
      <div className="applications-table-container">
        <table className="applications-table">
          <thead>
            <tr>
              <th>Candidat</th>
              <th>Classe visée</th>
              <th>Date de naissance</th>
              <th>Genre</th>
              <th>Parent / Tuteur</th>
              <th>Contact</th>
              <th>Date soumission</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {currentApplications.map(application => (
              <tr key={application.id}>
                <td>
                  <div className="applicant-info-cell">
                    <div className="applicant-avatar-sm">
                      {application.studentInfo.firstName.charAt(0)}
                      {application.studentInfo.lastName.charAt(0)}
                    </div>
                    <span className="applicant-fullname">
                      {application.studentInfo.firstName} {application.studentInfo.lastName}
                    </span>
                  </div>
                </td>
                <td><span className="class-badge">{application.appliedClass}</span></td>
                <td>{application.studentInfo.dateOfBirth.toLocaleDateString('fr-CD')}</td>
                <td>{application.studentInfo.gender === 'M' ? '👨 M' : '👩 F'}</td>
                <td>
                  {application.parentInfo.firstName} {application.parentInfo.lastName}
                </td>
                <td>
                  <div className="contact-cell">
                    <span>📧 {application.parentInfo.email}</span>
                    <span>📱 {application.parentInfo.phone}</span>
                  </div>
                </td>
                <td>{application.submittedAt.toLocaleDateString('fr-CD')}</td>
                <td>
                  <span className={`status-badge ${getStatusColor(application.status)}`}>
                    {getStatusLabel(application.status)}
                  </span>
                </td>
                <td>
                  <div className="row-actions">
                    {application.status === 'submitted' && (
                      <button
                        className="row-btn-text review"
                        onClick={() => handleStatusChange(application.id, 'under_review')}
                        title="Commencer la révision"
                      >🔍 Réviser</button>
                    )}
                    {application.status === 'under_review' && (
                      <>
                        <button
                          className="row-btn-text accept"
                          onClick={() => handleStatusChange(application.id, 'accepted')}
                          title="Accepter"
                        >✅ Accepter</button>
                        <button
                          className="row-btn-text reject"
                          onClick={() => handleStatusChange(application.id, 'rejected')}
                          title="Refuser"
                        >❌ Refuser</button>
                      </>
                    )}
                    {(application.status === 'accepted' || application.status === 'rejected') && (
                      <span className="action-done">—</span>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination 
          currentPage={currentPage}
          totalItems={filteredApplications.length}
          itemsPerPage={itemsPerPage}
          onPageChange={setCurrentPage}
        />
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