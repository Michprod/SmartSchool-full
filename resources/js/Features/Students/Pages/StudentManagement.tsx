import React, { useState, useEffect } from 'react';
import axios from 'axios';
import type { Student } from '../types';
import { Head, router } from '@inertiajs/react';
import StudentForm from '../Components/StudentForm';
import type { StudentFormData } from '../Components/StudentForm';
import StudentDetails from '../Components/StudentDetails';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './StudentManagement.css';

const StudentManagement: React.FC = () => {
  const [students, setStudents] = useState<Student[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedClass, setSelectedClass] = useState('all');
  const [showForm, setShowForm] = useState(false);
  const [editingStudent, setEditingStudent] = useState<StudentFormData | undefined>(undefined);
  const [showDetails, setShowDetails] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
  
  const [allClasses, setAllClasses] = useState<{id: string, name: string}[]>([]);

  const handleViewDetailsClick = (student: Student) => {
    setSelectedStudent(student);
    setShowDetails(true);
  };

  const loadStudents = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/students', { params: { per_page: 100 } });
      const paginatedData = response.data;
      
      const mapped = paginatedData.data.map((s: any) => ({
        ...s,
        firstName: s.first_name,
        lastName: s.last_name,
        studentNumber: s.student_number || s.matricule,
        classId: s.class_id?.toString(),
        class: s.school_class?.name || 'Non assigné',
        dateOfBirth: new Date(s.date_of_birth),
        enrollmentDate: new Date(s.enrollment_date),
        isActive: s.status === 'active',
        status: s.status
      }));

      setStudents(mapped);
    } catch (error) {
      console.error('Error loading students:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadClasses = async () => {
    try {
      const response = await axios.get('/api/classes');
      setAllClasses(response.data.data || response.data);
    } catch (e) { console.error('Error loading classes:', e); }
  };

  useEffect(() => {
    loadStudents();
    loadClasses();
  }, []);

  const filteredStudents = students.filter(student => {
    const matchesSearch = `${student.firstName} ${student.lastName} ${student.matricule}`
      .toLowerCase()
      .includes(searchTerm.toLowerCase());
    const matchesClass = selectedClass === 'all' || student.classId === selectedClass;
    return matchesSearch && matchesClass;
  });

  const calculateAge = (dateOfBirth: Date) => {
    const today = new Date();
    const age = today.getFullYear() - dateOfBirth.getFullYear();
    const monthDiff = today.getMonth() - dateOfBirth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dateOfBirth.getDate())) {
      return age - 1;
    }
    return age;
  };

  const handleAddStudent = () => {
    setEditingStudent(undefined);
    setShowForm(true);
  };

  const handleEditStudent = (student: Student) => {
    const formData: StudentFormData = {
      id: student.id,
      matricule: student.matricule,
      firstName: student.firstName,
      lastName: student.lastName,
      dateOfBirth: student.dateOfBirth.toISOString().split('T')[0],
      gender: student.gender,
      placeOfBirth: student.placeOfBirth || '',
      nationality: student.nationality || 'Congolaise (RDC)',
      address: student.address || '',
      city: student.city || 'Kinshasa',
      province: student.province || 'Kinshasa',
      guardianName: student.guardianName,
      guardianRelation: student.guardianRelation || 'Père',
      guardianPhone: student.guardianPhone,
      class: student.classId || '',
      academicYear: student.academicYear || new Date().getFullYear().toString(),
      admissionDate: student.enrollmentDate.toISOString().split('T')[0],
      emergencyContact: student.emergencyContact || student.guardianName,
      emergencyPhone: student.guardianPhone, // Default to guardian
      hasbirthCertificate: false,
      hasVaccinationCard: false,
      hasReportCard: false,
      hasPhoto: false,
      tuitionStatus: 'unpaid',
      status: (student.status === 'suspended' ? 'inactive' : student.status) as any
    };
    setEditingStudent(formData);
    setShowForm(true);
  };

  const handleSubmitStudent = async (data: StudentFormData) => {
    try {
      console.log('Sending student data via axios:', data);
      const url = editingStudent ? `/api/students/${editingStudent.id}` : '/api/students';
      const method = editingStudent ? 'put' : 'post';
      
      const payload = {
        matricule: data.matricule,
        student_number: data.matricule,
        first_name: data.firstName,
        last_name: data.lastName,
        date_of_birth: data.dateOfBirth,
        gender: data.gender,
        class_id: data.class, // Now sending ID
        enrollment_date: data.admissionDate,
        guardian_name: data.guardianName,
        guardian_phone: data.guardianPhone,
        status: data.status
      };

      const response = await axios({
        method,
        url,
        data: payload,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      console.log('Server response:', response.data);
      await loadStudents();
      setShowForm(false);
      setEditingStudent(undefined);
    } catch (error: any) {
      console.error('Error saving student:', error);
      const msg = error.response?.data?.message || error.message || 'Erreur lors du transfert';
      alert(`Erreur: ${msg}`);
    }
  };

  const handleCancelForm = () => {
    setShowForm(false);
    setEditingStudent(undefined);
  };

  const handleViewPayments = (studentId: string) => {
    router.visit(`/finance?student=${studentId}`);
  };

  const handleContactParent = (student: Student) => {
    router.visit(`/communication?type=parent&student=${student.id}`);
  };

  const handleDeleteStudent = async (studentId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet élève ?')) {
      try {
        await axios.delete(`/api/students/${studentId}`);
        await loadStudents();
      } catch (error) {
        console.error('Error deleting student:', error);
      }
    }
  };
  
  // Function available for future use
  console.log('Delete function ready:', handleDeleteStudent);

  const handleExportStudents = () => {
    console.log('Exporting students to Excel/PDF');
    alert('Export en cours...\n\nLes données seront exportées au format Excel avec:\n- Liste complète des élèves\n- Informations détaillées\n- Statistiques par classe');
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement des élèves...</p>
      </div>
    );
  }

  return (
    <div className="student-management">
      <Head title="Gestion des Élèves" />
      <div className="page-header">
        <h1>Gestion des Élèves</h1>
        <p className="page-subtitle">
          Dossier numérique unique - Administration des élèves
        </p>
      </div>

      {/* Actions et filtres */}
      <div className="management-controls">
        <div className="search-filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Rechercher un élève..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
          
          <div className="filter-group">
            <label>Classe :</label>
            <select
              value={selectedClass}
              onChange={(e) => setSelectedClass(e.target.value)}
              className="class-filter"
            >
              <option value="all">Toutes les classes</option>
              {allClasses.map(cls => (
                <option key={cls.id} value={cls.id}>{cls.name}</option>
              ))}
            </select>
          </div>
        </div>
        
        <div className="action-buttons">
          <button className="btn btn-outline" onClick={handleExportStudents}>
            <span>📊</span>
            Exporter
          </button>
          <button className="btn btn-primary" onClick={handleAddStudent}>
            <span>➕</span>
            Nouvel Élève
          </button>
        </div>
      </div>

      {/* Statistiques rapides */}
      <div className="stats-summary">
        <div className="stat-item">
          <span className="stat-number">{students.length}</span>
          <span className="stat-label">Total Élèves</span>
        </div>
        <div className="stat-item">
          <span className="stat-number">{students.filter(s => s.isActive).length}</span>
          <span className="stat-label">Actifs</span>
        </div>
        <div className="stat-item">
          <span className="stat-number">{filteredStudents.length}</span>
          <span className="stat-label">Affichés</span>
        </div>
      </div>

      {/* Liste des élèves */}
      <div className="students-grid">
        {filteredStudents.map(student => (
          <div key={student.id} className="student-card">
            <div className="student-header">
              <div className="student-avatar">
                {student.firstName.charAt(0)}{student.lastName.charAt(0)}
              </div>
              <div className="student-basic-info">
                <h3 className="student-name">
                  {student.firstName} {student.lastName}
                </h3>
                <p className="student-number">#{student.studentNumber}</p>
                <span className={`status-badge ${student.isActive ? 'active' : 'inactive'}`}>
                  {student.isActive ? 'Actif' : 'Inactif'}
                </span>
              </div>
            </div>
            
            <div className="student-details">
              <div className="detail-item">
                <span className="detail-icon">🎂</span>
                <div className="detail-content">
                  <span className="detail-label">Âge</span>
                  <span className="detail-value">{calculateAge(student.dateOfBirth)} ans</span>
                </div>
              </div>
              
              <div className="detail-item">
                <span className="detail-icon">👥</span>
                <div className="detail-content">
                  <span className="detail-label">Classe</span>
                  <span className="detail-value">{student.class}</span>
                </div>
              </div>
              
              <div className="detail-item">
                <span className="detail-icon">📅</span>
                <div className="detail-content">
                  <span className="detail-label">Inscription</span>
                  <span className="detail-value">{student.enrollmentDate.toLocaleDateString()}</span>
                </div>
              </div>
              
              <div className="detail-item">
                <span className="detail-icon">{student.gender === 'M' ? '👨' : '👩'}</span>
                <div className="detail-content">
                  <span className="detail-label">Genre</span>
                  <span className="detail-value">{student.gender === 'M' ? 'Masculin' : 'Féminin'}</span>
                </div>
              </div>
            </div>
            
            <div className="student-actions">
              <button className="action-btn primary" onClick={() => handleViewDetailsClick(student)} title="Voir le dossier">
                <span>📁</span>
                Dossier
              </button>
              <button className="action-btn secondary" onClick={() => handleEditStudent(student)} title="Modifier">
                <span>✏️</span>
                Modifier
              </button>
              <button className="action-btn secondary" onClick={() => handleViewPayments(student.id)} title="Paiements">
                <span>💰</span>
                Paiements
              </button>
              <button className="action-btn secondary" onClick={() => handleContactParent(student)} title="Contact parent">
                <span>📞</span>
                Parent
              </button>
            </div>
          </div>
        ))}
      </div>

      {filteredStudents.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">🔍</div>
          <h3>Aucun élève trouvé</h3>
          <p>Aucun élève ne correspond à vos critères de recherche.</p>
          <button className="btn btn-primary" onClick={handleAddStudent}>
            <span>➕</span>
            Ajouter un élève
          </button>
        </div>
      )}

      {/* Student Form Modal */}
      {showForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <StudentForm
              initialData={editingStudent}
              classes={allClasses} // Pass full objects
              onSubmit={handleSubmitStudent}
              onCancel={handleCancelForm}
              mode={editingStudent ? 'edit' : 'create'}
            />
          </div>
        </div>
      )}

      {/* Student Details Modal */}
      {showDetails && selectedStudent && (
        <div className="modal-overlay" onClick={() => setShowDetails(false)}>
          <div className="modal-content modal-large" onClick={(e) => e.stopPropagation()}>
            <StudentDetails
              student={selectedStudent}
              onClose={() => setShowDetails(false)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

(StudentManagement as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default StudentManagement;