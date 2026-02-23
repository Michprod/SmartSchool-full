import React, { useState, useEffect } from 'react';
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
  
  // Function to handle viewing student details
  const handleViewDetailsClick = (student: Student) => {
    setSelectedStudent(student);
    setShowDetails(true);
  };

  useEffect(() => {
    const loadStudents = async () => {
      setTimeout(() => {
        const mockStudents: Student[] = [
          {
            id: '1',
            matricule: 'STU-2024-001',
            studentNumber: 'STU-2024-001',
            firstName: 'Marie',
            lastName: 'Kalala',
            dateOfBirth: new Date('2010-05-15'),
            gender: 'F',
            placeOfBirth: 'Kinshasa',
            nationality: 'Congolaise (RDC)',
            bloodGroup: 'A+',
            city: 'Kinshasa',
            province: 'Kinshasa',
            classId: '6a',
            class: '6ème A',
            academicYear: '2024-2025',
            parentIds: ['parent1'],
            guardianName: 'Papa Kalala',
            guardianPhone: '+243 998 765 432',
            enrollmentDate: new Date('2024-01-15'),
            isActive: true,
            status: 'active'
          },
          {
            id: '2',
            matricule: 'STU-2024-002',
            studentNumber: 'STU-2024-002',
            firstName: 'Jean',
            lastName: 'Mukendi',
            dateOfBirth: new Date('2009-08-22'),
            gender: 'M',
            placeOfBirth: 'Lubumbashi',
            nationality: 'Congolaise (RDC)',
            city: 'Kinshasa',
            province: 'Kinshasa',
            classId: '7b',
            class: '7ème B',
            academicYear: '2024-2025',
            parentIds: ['parent2'],
            guardianName: 'Mama Mukendi',
            guardianPhone: '+243 999 876 543',
            enrollmentDate: new Date('2024-02-01'),
            isActive: true,
            status: 'active'
          },
          {
            id: '3',
            matricule: 'STU-2024-003',
            studentNumber: 'STU-2024-003',
            firstName: 'Sophie',
            lastName: 'Mbala',
            dateOfBirth: new Date('2011-12-10'),
            gender: 'F',
            placeOfBirth: 'Matadi',
            nationality: 'Congolaise (RDC)',
            bloodGroup: 'B+',
            city: 'Kinshasa',
            province: 'Kinshasa',
            classId: '5c',
            class: '5ème C',
            academicYear: '2024-2025',
            parentIds: ['parent3'],
            guardianName: 'Tonton Mbala',
            guardianPhone: '+243 990 765 432',
            enrollmentDate: new Date('2024-01-20'),
            isActive: true,
            status: 'active'
          }
        ];
        setStudents(mockStudents);
        setLoading(false);
      }, 1000);
    };

    loadStudents();
  }, []);

  const filteredStudents = students.filter(student => {
    const matchesSearch = `${student.firstName} ${student.lastName}`
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

  const getClassLabel = (classId: string) => {
    const classLabels: { [key: string]: string } = {
      '5c': '5ème C',
      '6a': '6ème A',
      '7b': '7ème B'
    };
    return classLabels[classId] || classId;
  };

  const handleAddStudent = () => {
    setEditingStudent(undefined);
    setShowForm(true);
  };

  const handleEditStudent = (student: Student) => {
    const formData: StudentFormData = {
      id: student.id,
      matricule: student.studentNumber,
      firstName: student.firstName,
      lastName: student.lastName,
      dateOfBirth: student.dateOfBirth.toISOString().split('T')[0],
      gender: student.gender,
      placeOfBirth: '',
      nationality: 'Congolaise (RDC)',
      address: '',
      city: '',
      province: '',
      guardianName: '',
      guardianRelation: 'Père',
      guardianPhone: '',
      class: getClassLabel(student.classId),
      academicYear: new Date().getFullYear().toString(),
      admissionDate: student.enrollmentDate.toISOString().split('T')[0],
      emergencyContact: '',
      emergencyPhone: '',
      hasbirthCertificate: false,
      hasVaccinationCard: false,
      hasReportCard: false,
      hasPhoto: false,
      tuitionStatus: 'unpaid',
      status: student.isActive ? 'active' : 'inactive'
    };
    setEditingStudent(formData);
    setShowForm(true);
  };

  const handleSubmitStudent = (data: StudentFormData) => {
    if (editingStudent) {
      // Update existing student
      console.log('Updating student:', data);
    } else {
      // Add new student
      console.log('Adding new student:', data);
    }
    setShowForm(false);
    setEditingStudent(undefined);
  };

  const handleCancelForm = () => {
    setShowForm(false);
    setEditingStudent(undefined);
  };

  const handleViewPayments = (studentId: string) => {
    // Navigate to finance page with student filter
    router.visit(`/finance?student=${studentId}`);
  };

  const handleContactParent = (student: Student) => {
    // Navigate to communication page
    router.visit(`/communication?type=parent&student=${student.id}`);
  };

  const handleDeleteStudent = (studentId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet élève ? Cette action est irréversible.')) {
      setStudents(prev => prev.filter(s => s.id !== studentId));
      console.log('Student deleted:', studentId);
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
              <option value="5c">5ème C</option>
              <option value="6a">6ème A</option>
              <option value="7b">7ème B</option>
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
                  <span className="detail-value">{getClassLabel(student.classId)}</span>
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