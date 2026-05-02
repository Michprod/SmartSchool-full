import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import ProfileManagement from '../Components/ProfileManagement';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import './SettingsPage.css';
import axios from 'axios';

interface SchoolSettings {
  // General
  schoolName: string;
  schoolCode: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  province: string;
  logo: string;
  
  // Academic
  currentSchoolYear: string;
  termsPerYear: number;
  classLevels: string[];
  passingGrade: number;
  maxStudentsPerClass: number;
  
  // Financial
  defaultCurrency: 'CDF' | 'USD';
  exchangeRate: number;
  autoConversion: boolean;
  paymentMethods: string[];
  lateFeePercentage: number;
  
  // Notifications
  emailNotificationsEnabled: boolean;
  smsNotificationsEnabled: boolean;
  parentNotifications: boolean;
  teacherNotifications: boolean;
  paymentReminders: boolean;
  eventReminders: boolean;
  
  // System
  maintenanceMode: boolean;
  allowSelfRegistration: boolean;
  requireEmailVerification: boolean;
  sessionTimeout: number;
  backupFrequency: string;
  dataRetentionYears: number;
}

const SettingsPage: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'general' | 'academic' | 'financial' | 'notifications' | 'system' | 'profiles'>('general');
  const [isSaving, setIsSaving] = useState(false);

  const [settings, setSettings] = useState<SchoolSettings>({
    // General
    schoolName: 'SmartSchool RDC',
    schoolCode: 'SS-KIN-001',
    email: 'info@smartschool.cd',
    phone: '+243 81 234 5678',
    address: 'Avenue de la Libération',
    city: 'Kinshasa',
    province: 'Kinshasa',
    logo: '',
    
    // Academic
    currentSchoolYear: '2024-2025',
    termsPerYear: 3,
    classLevels: ['Maternelle', 'Primaire', 'Secondaire'],
    passingGrade: 50,
    maxStudentsPerClass: 40,
    
    // Financial
    defaultCurrency: 'CDF',
    exchangeRate: 2800,
    autoConversion: true,
    paymentMethods: ['Espèces', 'Mobile Money', 'Virement bancaire'],
    lateFeePercentage: 5,
    
    // Notifications
    emailNotificationsEnabled: true,
    smsNotificationsEnabled: true,
    parentNotifications: true,
    teacherNotifications: true,
    paymentReminders: true,
    eventReminders: true,
    
    // System
    maintenanceMode: false,
    allowSelfRegistration: false,
    requireEmailVerification: true,
    sessionTimeout: 30,
    backupFrequency: 'daily',
    dataRetentionYears: 7
  });

  const provinces = [
    'Kinshasa', 'Kongo-Central', 'Kwango', 'Kwilu', 'Mai-Ndombe',
    'Kasaï', 'Kasaï-Central', 'Kasaï-Oriental', 'Lomami', 'Sankuru',
    'Maniema', 'Sud-Kivu', 'Nord-Kivu', 'Ituri', 'Haut-Uélé', 'Tshopo', 'Bas-Uélé',
    'Nord-Ubangi', 'Mongala', 'Tshuapa', 'Équateur', 'Sud-Ubangi',
    'Lualaba', 'Haut-Katanga', 'Tanganyika', 'Haut-Lomami'
  ];

  React.useEffect(() => {
    const loadSettings = async () => {
      try {
        const response = await axios.get('/api/settings');
        if (response.data && Object.keys(response.data).length > 0) {
          setSettings(response.data);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des paramètres:', error);
      }
    };
    loadSettings();
  }, []);

  const handleSaveSettings = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    
    try {
      await axios.post('/api/settings', settings);
      alert('Paramètres enregistrés avec succès !');
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error);
      alert('Erreur lors de la sauvegarde des paramètres.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleExportSettings = () => {
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `smartschool-settings-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
  };

  const handleImportSettings = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      try {
        const imported = JSON.parse(event.target?.result as string);
        setSettings(imported);
        alert('Paramètres importés avec succès !');
      } catch (error) {
        alert('Erreur lors de l\'importation des paramètres');
      }
    };
    reader.readAsText(file);
  };

  const handleResetSettings = () => {
    if (window.confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ? Cette action est irréversible.')) {
      // Reset to default values
      alert('Paramètres réinitialisés (fonctionnalité à implémenter)');
    }
  };

  return (
    <div className="settings-page">
      <Head title="Paramètres du système" />
      <div className="page-header">
        <div>
          <h1>Paramètres du système</h1>
          <p className="page-subtitle">Configuration globale de SmartSchool RDC</p>
        </div>
        <div className="header-actions">
          <button className="btn btn-secondary" onClick={handleExportSettings}>
            📥 Exporter
          </button>
          <label className="btn btn-secondary">
            📤 Importer
            <input type="file" accept=".json" onChange={handleImportSettings} style={{ display: 'none' }} />
          </label>
        </div>
      </div>

      <div className="settings-tabs">
        <button
          className={`tab ${activeTab === 'general' ? 'active' : ''}`}
          onClick={() => setActiveTab('general')}
        >
          🏫 Général
        </button>
        <button
          className={`tab ${activeTab === 'academic' ? 'active' : ''}`}
          onClick={() => setActiveTab('academic')}
        >
          📚 Académique
        </button>
        <button
          className={`tab ${activeTab === 'financial' ? 'active' : ''}`}
          onClick={() => setActiveTab('financial')}
        >
          💰 Financier
        </button>
        <button
          className={`tab ${activeTab === 'profiles' ? 'active' : ''}`}
          onClick={() => setActiveTab('profiles')}
        >
          👥 Profils & Permissions
        </button>
        <button
          className={`tab ${activeTab === 'notifications' ? 'active' : ''}`}
          onClick={() => setActiveTab('notifications')}
        >
          🔔 Notifications
        </button>
        <button
          className={`tab ${activeTab === 'system' ? 'active' : ''}`}
          onClick={() => setActiveTab('system')}
        >
          ⚙️ Système
        </button>
      </div>

      {/* Content */}
      {activeTab === 'profiles' ? (
        <div className="settings-section">
          <ProfileManagement />
        </div>
      ) : (
        <form onSubmit={handleSaveSettings} className="settings-content">
        {/* General Settings */}
        {activeTab === 'general' && (
          <div className="settings-section">
            <h2>Informations générales</h2>
            
            <div className="form-group">
              <label>Nom de l'établissement <span className="required">*</span></label>
              <input
                type="text"
                value={settings.schoolName}
                onChange={(e) => setSettings({...settings, schoolName: e.target.value})}
                required
              />
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Code établissement <span className="required">*</span></label>
                <input
                  type="text"
                  value={settings.schoolCode}
                  onChange={(e) => setSettings({...settings, schoolCode: e.target.value})}
                  required
                />
              </div>
              <div className="form-group">
                <label>Email <span className="required">*</span></label>
                <input
                  type="email"
                  value={settings.email}
                  onChange={(e) => setSettings({...settings, email: e.target.value})}
                  required
                />
              </div>
            </div>

            <div className="form-group">
              <label>Téléphone <span className="required">*</span></label>
              <input
                type="tel"
                value={settings.phone}
                onChange={(e) => setSettings({...settings, phone: e.target.value})}
                required
              />
            </div>

            <div className="form-group">
              <label>Adresse</label>
              <input
                type="text"
                value={settings.address}
                onChange={(e) => setSettings({...settings, address: e.target.value})}
              />
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Ville</label>
                <input
                  type="text"
                  value={settings.city}
                  onChange={(e) => setSettings({...settings, city: e.target.value})}
                />
              </div>
              <div className="form-group">
                <label>Province</label>
                <select
                  value={settings.province}
                  onChange={(e) => setSettings({...settings, province: e.target.value})}
                >
                  {provinces.map(prov => (
                    <option key={prov} value={prov}>{prov}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Academic Settings */}
        {activeTab === 'academic' && (
          <div className="settings-section">
            <h2>Paramètres académiques</h2>
            
            <div className="form-row">
              <div className="form-group">
                <label>Année scolaire en cours <span className="required">*</span></label>
                <input
                  type="text"
                  value={settings.currentSchoolYear}
                  onChange={(e) => setSettings({...settings, currentSchoolYear: e.target.value})}
                  placeholder="2024-2025"
                  required
                />
              </div>
              <div className="form-group">
                <label>Nombre de trimestres par an</label>
                <select
                  value={settings.termsPerYear}
                  onChange={(e) => setSettings({...settings, termsPerYear: parseInt(e.target.value)})}
                >
                  <option value="2">2 Semestres</option>
                  <option value="3">3 Trimestres</option>
                  <option value="4">4 Trimestres</option>
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Note de passage (%)</label>
                <input
                  type="number"
                  min="0"
                  max="100"
                  value={settings.passingGrade}
                  onChange={(e) => setSettings({...settings, passingGrade: parseInt(e.target.value)})}
                />
              </div>
              <div className="form-group">
                <label>Nombre maximum d'élèves par classe</label>
                <input
                  type="number"
                  min="1"
                  max="100"
                  value={settings.maxStudentsPerClass}
                  onChange={(e) => setSettings({...settings, maxStudentsPerClass: parseInt(e.target.value)})}
                />
              </div>
            </div>

            <div className="info-box">
              <strong>📊 Niveaux d'enseignement configurés:</strong>
              <ul>
                {settings.classLevels.map((level, index) => (
                  <li key={index}>{level}</li>
                ))}
              </ul>
            </div>
          </div>
        )}

        {/* Financial Settings */}
        {activeTab === 'financial' && (
          <div className="settings-section">
            <h2>Paramètres financiers</h2>
            
            <div className="form-row">
              <div className="form-group">
                <label>Devise par défaut</label>
                <select
                  value={settings.defaultCurrency}
                  onChange={(e) => setSettings({...settings, defaultCurrency: e.target.value as 'CDF' | 'USD'})}
                >
                  <option value="CDF">Franc Congolais (CDF)</option>
                  <option value="USD">Dollar US (USD)</option>
                </select>
              </div>
              <div className="form-group">
                <label>Taux de change (1 USD = ? CDF)</label>
                <input
                  type="number"
                  min="1"
                  value={settings.exchangeRate}
                  onChange={(e) => setSettings({...settings, exchangeRate: parseInt(e.target.value)})}
                />
              </div>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Conversion automatique des devises</strong>
                <p>Permettre la conversion automatique entre CDF et USD</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.autoConversion}
                  onChange={(e) => setSettings({...settings, autoConversion: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="form-group">
              <label>Frais de retard (%)</label>
              <input
                type="number"
                min="0"
                max="100"
                step="0.1"
                value={settings.lateFeePercentage}
                onChange={(e) => setSettings({...settings, lateFeePercentage: parseFloat(e.target.value)})}
              />
              <small>Pourcentage appliqué aux paiements en retard</small>
            </div>

            <div className="info-box">
              <strong>💳 Méthodes de paiement acceptées:</strong>
              <ul>
                {settings.paymentMethods.map((method, index) => (
                  <li key={index}>{method}</li>
                ))}
              </ul>
            </div>
          </div>
        )}

        {/* Notifications Settings */}
        {activeTab === 'notifications' && (
          <div className="settings-section">
            <h2>Paramètres de notification</h2>
            
            <h3>Canaux de notification</h3>
            <div className="toggle-option">
              <div>
                <strong>Notifications par email</strong>
                <p>Envoyer des notifications importantes par email</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.emailNotificationsEnabled}
                  onChange={(e) => setSettings({...settings, emailNotificationsEnabled: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Notifications par SMS</strong>
                <p>Envoyer des alertes urgentes par SMS</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.smsNotificationsEnabled}
                  onChange={(e) => setSettings({...settings, smsNotificationsEnabled: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <h3>Types de notifications</h3>
            <div className="toggle-option">
              <div>
                <strong>Notifications aux parents</strong>
                <p>Alertes sur les absences, notes, paiements</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.parentNotifications}
                  onChange={(e) => setSettings({...settings, parentNotifications: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Notifications aux enseignants</strong>
                <p>Alertes sur les événements, absences, réunions</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.teacherNotifications}
                  onChange={(e) => setSettings({...settings, teacherNotifications: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Rappels de paiement</strong>
                <p>Envoyer des rappels automatiques pour les paiements dus</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.paymentReminders}
                  onChange={(e) => setSettings({...settings, paymentReminders: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Rappels d'événements</strong>
                <p>Notifier les événements à venir (examens, réunions, etc.)</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.eventReminders}
                  onChange={(e) => setSettings({...settings, eventReminders: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>
          </div>
        )}

        {/* System Settings */}
        {activeTab === 'profiles' && (
          <div className="settings-section">
            <ProfileManagement />
          </div>
        )}

        {activeTab === 'system' && (
          <div className="settings-section">
            <h2>Paramètres système</h2>
            
            <div className="warning-box">
              <strong>⚠️ Attention:</strong> Ces paramètres affectent le fonctionnement global du système.
            </div>

            <h3>Sécurité et accès</h3>
            <div className="toggle-option">
              <div>
                <strong>Mode maintenance</strong>
                <p>Désactiver l'accès au système pour maintenance</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.maintenanceMode}
                  onChange={(e) => setSettings({...settings, maintenanceMode: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Auto-inscription</strong>
                <p>Permettre aux nouveaux utilisateurs de s'inscrire eux-mêmes</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.allowSelfRegistration}
                  onChange={(e) => setSettings({...settings, allowSelfRegistration: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="toggle-option">
              <div>
                <strong>Vérification email obligatoire</strong>
                <p>Exiger la vérification de l'email pour les nouveaux comptes</p>
              </div>
              <label className="toggle-switch">
                <input
                  type="checkbox"
                  checked={settings.requireEmailVerification}
                  onChange={(e) => setSettings({...settings, requireEmailVerification: e.target.checked})}
                />
                <span className="toggle-slider"></span>
              </label>
            </div>

            <div className="form-group">
              <label>Durée de session (minutes)</label>
              <input
                type="number"
                min="5"
                max="1440"
                value={settings.sessionTimeout}
                onChange={(e) => setSettings({...settings, sessionTimeout: parseInt(e.target.value)})}
              />
              <small>Déconnexion automatique après inactivité</small>
            </div>

            <h3>Sauvegarde et données</h3>
            <div className="form-group">
              <label>Fréquence de sauvegarde</label>
              <select
                value={settings.backupFrequency}
                onChange={(e) => setSettings({...settings, backupFrequency: e.target.value})}
              >
                <option value="hourly">Toutes les heures</option>
                <option value="daily">Quotidienne</option>
                <option value="weekly">Hebdomadaire</option>
                <option value="monthly">Mensuelle</option>
              </select>
            </div>

            <div className="form-group">
              <label>Durée de conservation des données (années)</label>
              <input
                type="number"
                min="1"
                max="50"
                value={settings.dataRetentionYears}
                onChange={(e) => setSettings({...settings, dataRetentionYears: parseInt(e.target.value)})}
              />
              <small>Durée légale de conservation des données scolaires</small>
            </div>

            <div className="danger-zone">
              <h3>⚠️ Zone dangereuse</h3>
              <button
                type="button"
                className="btn btn-danger"
                onClick={handleResetSettings}
              >
                🔄 Réinitialiser tous les paramètres
              </button>
            </div>
          </div>
        )}

        <div className="form-actions sticky-actions">
          <button type="button" className="btn btn-secondary" onClick={() => window.history.back()}>
            Annuler
          </button>
          <button type="submit" className="btn btn-primary" disabled={isSaving}>
            {isSaving ? '💾 Enregistrement...' : '💾 Enregistrer les paramètres'}
          </button>
        </div>
      </form>
      )}
    </div>
  );
};

(SettingsPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default SettingsPage;
