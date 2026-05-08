import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import Can from '../../../Core/Components/Can';
import './GradesPage.css';

interface Student {
  id: number;
  first_name: string;
  last_name: string;
  matricule: string;
}

interface SchoolClass {
  id: number;
  name: string;
  level: string;
}

interface Subject {
  id: number;
  name: string;
  code: string;
}

const GradesPage: React.FC = () => {
  const { auth } = usePage<any>().props;
  const [activeTab, setActiveTab] = useState<'overview' | 'input' | 'averages' | 'reports'>('overview');
  
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [students, setStudents] = useState<Student[]>([]);
  
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSubject, setSelectedSubject] = useState('');
  const [term, setTerm] = useState('T1');
  const [academicYear, setAcademicYear] = useState('2025-2026');
  const [assessmentType, setAssessmentType] = useState('devoir');
  const [maxScore, setMaxScore] = useState(20);
  const [coefficient, setCoefficient] = useState(1);
  const [assessmentTitle, setAssessmentTitle] = useState('');
  
  // Grade input state
  const [gradesInput, setGradesInput] = useState<Record<number, { score: string, comment: string }>>({});
  
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      const [classesRes, subjectsRes] = await Promise.all([
        axios.get('/api/grades/my-classes'),
        axios.get('/api/subjects')
      ]);
      setClasses(classesRes.data.data || classesRes.data);
      setSubjects(subjectsRes.data.data || subjectsRes.data);
    } catch (error) {
      console.error('Error fetching initial data', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedClass) {
      fetchStudents(selectedClass);
      // Filter subjects for this class
      const classData = classes.find((c: any) => c.class.id === parseInt(selectedClass));
      if (classData && classData.subjects) {
        setSubjects(classData.subjects.map((s: any) => s.subject));
      }
    } else {
      setStudents([]);
      // Reset subjects to all subjects or empty
      fetchInitialData();
    }
  }, [selectedClass]);

  const fetchStudents = async (classId: string) => {
    try {
      const response = await axios.get(`/api/grades/classes/${classId}/students`);
      const studentsList = response.data.students || response.data;
      setStudents(studentsList);
      
      // Initialize grades input state
      const initialGrades: Record<number, { score: string, comment: string }> = {};
      if (Array.isArray(studentsList)) {
        studentsList.forEach((s: Student) => {
          initialGrades[s.id] = { score: '', comment: '' };
        });
      }
      setGradesInput(initialGrades);
    } catch (error) {
      console.error('Error fetching students', error);
    }
  };

  const handleGradeChange = (studentId: number, field: 'score' | 'comment', value: string) => {
    setGradesInput(prev => ({
      ...prev,
      [studentId]: {
        ...prev[studentId],
        [field]: value
      }
    }));
  };

  const saveGrades = async () => {
    if (!selectedClass || !selectedSubject || !assessmentTitle) {
      alert("Veuillez remplir la classe, la matière et le titre de l'évaluation.");
      return;
    }

    try {
      setSaving(true);
      const payload = {
        class_id: selectedClass,
        subject_id: selectedSubject,
        term,
        academic_year: academicYear,
        type: assessmentType,
        max_score: maxScore,
        coefficient,
        title: assessmentTitle,
        grades: Object.entries(gradesInput)
          .filter(([_, data]) => data.score !== '')
          .map(([studentId, data]) => ({
            student_id: studentId,
            score: data.score,
            comment: data.comment
          }))
      };

      await axios.post('/api/grades/bulk', payload);
      alert('Notes enregistrées avec succès !');
      // Clear inputs
      const clearedGrades: Record<number, { score: string, comment: string }> = {};
      students.forEach(s => {
        clearedGrades[s.id] = { score: '', comment: '' };
      });
      setGradesInput(clearedGrades);
      setAssessmentTitle('');
    } catch (error) {
      console.error('Error saving grades', error);
      alert("Erreur lors de l'enregistrement des notes.");
    } finally {
      setSaving(false);
    }
  };

  const calculateAverages = async () => {
    if (!selectedClass || !term) {
      alert("Veuillez sélectionner une classe et un trimestre.");
      return;
    }
    try {
      setSaving(true);
      await axios.post(`/api/grades/classes/${selectedClass}/calculate`, {
        term,
        academic_year: academicYear
      });
      alert('Moyennes calculées avec succès !');
    } catch (error) {
      console.error('Error calculating averages', error);
      alert('Erreur lors du calcul des moyennes.');
    } finally {
      setSaving(false);
    }
  };

  const generateReportCards = async () => {
    if (!selectedClass || !term) {
      alert("Veuillez sélectionner une classe et un trimestre.");
      return;
    }
    try {
      setSaving(true);
      // For each student in the class, generate report card
      for (const student of students) {
        await axios.post(`/api/grades/students/${student.id}/report-card`, {
          term,
          academic_year: academicYear
        });
      }
      alert('Bulletins générés avec succès pour toute la classe !');
    } catch (error) {
      console.error('Error generating report cards', error);
      alert('Erreur lors de la génération des bulletins.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="grades-page">
      <Head title="Gestion des Notes" />
      
      <div className="page-header">
        <h1>Gestion des Notes & Évaluations</h1>
        <p className="page-subtitle">Saisie des notes, calcul des moyennes et édition des bulletins</p>
      </div>

      {/* Global Selectors */}
      <div className="global-selectors">
        <div className="selector-group">
          <label>Année Académique</label>
          <select value={academicYear} onChange={(e) => setAcademicYear(e.target.value)}>
            <option value="2024-2025">2024-2025</option>
            <option value="2025-2026">2025-2026</option>
          </select>
        </div>
        <div className="selector-group">
          <label>Trimestre/Période</label>
          <select value={term} onChange={(e) => setTerm(e.target.value)}>
            <option value="T1">1er Trimestre (T1)</option>
            <option value="T2">2ème Trimestre (T2)</option>
            <option value="T3">3ème Trimestre (T3)</option>
          </select>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="grades-tabs">
        <button 
          className={`tab-btn ${activeTab === 'overview' ? 'active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          📊 Vue d'ensemble
        </button>
        <button 
          className={`tab-btn ${activeTab === 'input' ? 'active' : ''}`}
          onClick={() => setActiveTab('input')}
        >
          ✏️ Saisie des notes
        </button>
        <button 
          className={`tab-btn ${activeTab === 'averages' ? 'active' : ''}`}
          onClick={() => setActiveTab('averages')}
        >
          📈 Moyennes
        </button>
        <button 
          className={`tab-btn ${activeTab === 'reports' ? 'active' : ''}`}
          onClick={() => setActiveTab('reports')}
        >
          📄 Bulletins
        </button>
      </div>

      <div className="grades-content">
        
        {/* OVERVIEW TAB */}
        {activeTab === 'overview' && (
          <div className="tab-pane overview-pane">
            <div className="overview-cards">
              <div className="card">
                <h3>Mes Classes</h3>
                <p className="big-number">{classes.length}</p>
                <button className="btn-link" onClick={() => setActiveTab('input')}>Commencer la saisie →</button>
              </div>
              <div className="card">
                <h3>Trimestre Actif</h3>
                <p className="big-number">{term}</p>
                <button className="btn-link" onClick={() => setActiveTab('averages')}>Voir les moyennes →</button>
              </div>
            </div>
          </div>
        )}

        {/* INPUT TAB */}
        {activeTab === 'input' && (
          <div className="tab-pane input-pane">
            <div className="input-controls">
              <div className="control-row">
                <div className="form-group">
                  <label>Classe *</label>
                  <select value={selectedClass} onChange={(e) => setSelectedClass(e.target.value)}>
                    <option value="">Sélectionner une classe</option>
                    {classes.map((c: any) => <option key={c.class.id} value={c.class.id}>{c.class.name}</option>)}
                  </select>
                </div>
                <div className="form-group">
                  <label>Matière *</label>
                  <select value={selectedSubject} onChange={(e) => setSelectedSubject(e.target.value)}>
                    <option value="">Sélectionner une matière</option>
                    {subjects.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                  </select>
                </div>
              </div>
              
              <div className="control-row">
                <div className="form-group">
                  <label>Titre de l'évaluation *</label>
                  <input 
                    type="text" 
                    placeholder="Ex: Interrogation 1, Examen..." 
                    value={assessmentTitle}
                    onChange={(e) => setAssessmentTitle(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label>Type</label>
                  <select value={assessmentType} onChange={(e) => setAssessmentType(e.target.value)}>
                    <option value="devoir">Devoir</option>
                    <option value="interrogation">Interrogation</option>
                    <option value="composition">Composition</option>
                    <option value="examen">Examen</option>
                    <option value="projet">Projet</option>
                    <option value="participation">Participation</option>
                  </select>
                </div>
                <div className="form-group-small">
                  <label>Note sur</label>
                  <input type="number" value={maxScore} onChange={(e) => setMaxScore(Number(e.target.value))} />
                </div>
                <div className="form-group-small">
                  <label>Coefficient</label>
                  <input type="number" value={coefficient} onChange={(e) => setCoefficient(Number(e.target.value))} />
                </div>
              </div>
            </div>

            {selectedClass && students.length > 0 ? (
              <div className="grades-table-container">
                <table className="grades-table">
                  <thead>
                    <tr>
                      <th>Élève</th>
                      <th>Note /{maxScore}</th>
                      <th>Commentaire (Optionnel)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {students.map(student => (
                      <tr key={student.id}>
                        <td>{student.first_name} {student.last_name}</td>
                        <td>
                          <input 
                            type="number" 
                            step="0.5"
                            min="0"
                            max={maxScore}
                            className="grade-input"
                            value={gradesInput[student.id]?.score || ''}
                            onChange={(e) => handleGradeChange(student.id, 'score', e.target.value)}
                            placeholder="-"
                          />
                        </td>
                        <td>
                          <input 
                            type="text" 
                            className="comment-input"
                            value={gradesInput[student.id]?.comment || ''}
                            onChange={(e) => handleGradeChange(student.id, 'comment', e.target.value)}
                            placeholder="Observations..."
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="form-actions">
                  <Can permission="grades:write">
                    <button className="btn btn-primary" onClick={saveGrades} disabled={saving}>
                      {saving ? 'Enregistrement...' : '💾 Enregistrer les notes'}
                    </button>
                  </Can>
                </div>
              </div>
            ) : selectedClass ? (
              <div className="empty-state">Aucun élève dans cette classe.</div>
            ) : (
              <div className="empty-state">Veuillez sélectionner une classe pour saisir les notes.</div>
            )}
          </div>
        )}

        {/* AVERAGES TAB */}
        {activeTab === 'averages' && (
          <div className="tab-pane averages-pane">
             <div className="control-row">
                <div className="form-group">
                  <label>Classe</label>
                  <select value={selectedClass} onChange={(e) => setSelectedClass(e.target.value)}>
                    <option value="">Sélectionner une classe</option>
                    {classes.map((c: any) => <option key={c.class.id} value={c.class.id}>{c.class.name}</option>)}
                  </select>
                </div>
                <Can permission="grades:write">
                  <button className="btn btn-primary" onClick={calculateAverages} disabled={!selectedClass || saving}>
                    {saving ? 'Calcul...' : '🧮 Calculer toutes les moyennes'}
                  </button>
                </Can>
              </div>
              
              {selectedClass && (
                <div className="empty-state" style={{marginTop: '20px'}}>
                  <p>Les moyennes seront affichées ici après le calcul.</p>
                </div>
              )}
          </div>
        )}

        {/* REPORTS TAB */}
        {activeTab === 'reports' && (
          <div className="tab-pane reports-pane">
            <div className="control-row">
                <div className="form-group">
                  <label>Classe</label>
                  <select value={selectedClass} onChange={(e) => setSelectedClass(e.target.value)}>
                    <option value="">Sélectionner une classe</option>
                    {classes.map((c: any) => <option key={c.class.id} value={c.class.id}>{c.class.name}</option>)}
                  </select>
                </div>
                <Can permission="grades:write">
                  <button 
                    className="btn btn-secondary" 
                    disabled={!selectedClass || saving}
                    onClick={generateReportCards}
                  >
                    {saving ? 'Génération...' : '📄 Générer tous les bulletins'}
                  </button>
                </Can>
              </div>
              
              {selectedClass && (
                <div className="empty-state" style={{marginTop: '20px'}}>
                  <p>La liste des élèves et leurs bulletins sera générée ici.</p>
                </div>
              )}
          </div>
        )}

      </div>
    </div>
  );
};

(GradesPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default GradesPage;
