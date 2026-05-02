import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { SchoolEvent } from '../types';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import Pagination from '../../../Core/Components/Pagination';
import './EventsPage.css';
import axios from 'axios';

const EventsPage: React.FC = () => {
  const [events, setEvents] = useState<SchoolEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedFilter, setSelectedFilter] = useState<'all' | 'upcoming' | 'past'>('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedEvent, setSelectedEvent] = useState<SchoolEvent | null>(null);
  const [showEventModal, setShowEventModal] = useState(false);

  const [newEvent, setNewEvent] = useState<Partial<SchoolEvent>>({
    title: '',
    description: '',
    date: new Date(),
    location: '',
    organizer: '',
    media: []
  });

  const [viewMode, setViewMode] = useState<'list' | 'calendar'>('list');

  const resetNewEvent = () => {
    setNewEvent({
      title: '',
      description: '',
      date: new Date(),
      location: '',
      organizer: '',
      media: []
    });
  };

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm, selectedFilter]);

  const loadEvents = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/events');
      const data = response.data?.data || response.data || [];
      const eventsWithDates = (Array.isArray(data) ? data : []).map((e: any) => ({
        ...e,
        date: new Date(e.date)
      }));
      setEvents(eventsWithDates);
    } catch (error) {
      console.error('Error loading events:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadEvents();
  }, []);

  const filteredEvents = events.filter(event => {
    const matchesSearch = event.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.location.toLowerCase().includes(searchTerm.toLowerCase());
    
    const now = new Date();
    const matchesFilter = selectedFilter === 'all' || 
                         (selectedFilter === 'upcoming' && event.date > now) ||
                         (selectedFilter === 'past' && event.date <= now);
    
    return matchesSearch && matchesFilter;
  });

  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentEvents = filteredEvents.slice(indexOfFirstItem, indexOfLastItem);

  const upcomingEvents = events.filter(event => event.date > new Date());
  const pastEvents = events.filter(event => event.date <= new Date());

  const handleEditEvent = (event: SchoolEvent) => {
    setSelectedEvent(event);
    setNewEvent({
      ...event,
      date: new Date(event.date)
    });
    setShowCreateModal(true);
  };

  const handleSaveEvent = async () => {
    try {
      const isEditing = !!(newEvent as any).id;
      const url = isEditing ? `/api/events/${(newEvent as any).id}` : '/api/events';
      const method = isEditing ? 'put' : 'post';
      
      const response = await axios({
        method,
        url,
        data: newEvent
      });
      
      const savedEvent = {
        ...response.data,
        date: new Date(response.data.date)
      };
      
      if (isEditing) {
        setEvents(prev => prev.map(e => e.id === savedEvent.id ? savedEvent : e));
      } else {
        setEvents(prev => [savedEvent, ...prev]);
      }
      
      resetNewEvent();
      setShowCreateModal(false);
    } catch (e: any) {
      console.error('Error saving event:', e);
      alert('Erreur lors de l\'enregistrement de l\'événement.');
    }
  };

  const handleDeleteEvent = async (eventId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
      try {
        await axios.delete(`/api/events/${eventId}`);
        setEvents(prev => prev.filter(event => event.id !== eventId));
      } catch (e: any) {
        console.error('Error deleting event:', e);
        alert('Erreur lors de la suppression.');
      }
    }
  };

  const handleMediaUpload = (files: FileList | null) => {
    if (!files) return;
    
    const newMediaFiles = Array.from(files).map(file => {
      const fileType = file.type.startsWith('image/') ? 'image' : 
                      file.type.startsWith('video/') ? 'video' : 'document';
      
      return {
        type: fileType as 'image' | 'video' | 'document',
        url: URL.createObjectURL(file),
        name: file.name,
        size: file.size
      };
    });
    
    setNewEvent(prev => ({
      ...prev,
      media: [...(prev.media || []), ...newMediaFiles]
    }));
  };

  const handleRemoveMedia = (index: number) => {
    setNewEvent(prev => ({
      ...prev,
      media: prev.media?.filter((_, i) => i !== index) || []
    }));
  };

  const getMediaIcon = (type: string) => {
    switch (type) {
      case 'image': return '🖼️';
      case 'video': return '🎥';
      case 'document': return '📄';
      default: return '📎';
    }
  };

  const formatFileSize = (bytes?: number) => {
    if (!bytes) return '';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  };

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const formatDateShort = (date: Date) => {
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  };

  const isUpcoming = (date: Date) => date > new Date();

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement des événements...</p>
      </div>
    );
  }

  return (
    <div className="events-page">
      <Head title="Gestion des Événements" />
      <div className="page-header">
        <h1>Gestion des Événements</h1>
        <p className="page-subtitle">
          Planification et suivi des activités scolaires
        </p>
      </div>

      {/* Statistiques rapides */}
      <div className="events-stats">
        <div className="stat-card total">
          <div className="stat-icon">📅</div>
          <div className="stat-content">
            <span className="stat-number">{events.length}</span>
            <span className="stat-label">Total Événements</span>
          </div>
        </div>
        <div className="stat-card upcoming">
          <div className="stat-icon">⏰</div>
          <div className="stat-content">
            <span className="stat-number">{upcomingEvents.length}</span>
            <span className="stat-label">À Venir</span>
          </div>
        </div>
        <div className="stat-card past">
          <div className="stat-icon">✅</div>
          <div className="stat-content">
            <span className="stat-number">{pastEvents.length}</span>
            <span className="stat-label">Terminés</span>
          </div>
        </div>
      </div>

      {/* Contrôles de gestion */}
      <div className="management-controls">
        <div className="search-filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Rechercher un événement..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
          
          <div className="filter-group">
            <label>Filtrer :</label>
            <select
              value={selectedFilter}
              onChange={(e) => setSelectedFilter(e.target.value as 'all' | 'upcoming' | 'past')}
              className="filter-select"
            >
              <option value="all">Tous les événements</option>
              <option value="upcoming">À venir</option>
              <option value="past">Terminés</option>
            </select>
          </div>
        </div>
        
        <div className="action-buttons">
          <div className="view-mode-toggle">
            <button 
              className={`toggle-btn ${viewMode === 'list' ? 'active' : ''}`}
              onClick={() => setViewMode('list')}
              title="Vue Liste"
            >
              📋
            </button>
            <button 
              className={`toggle-btn ${viewMode === 'calendar' ? 'active' : ''}`}
              onClick={() => setViewMode('calendar')}
              title="Vue Calendrier"
            >
              📅
            </button>
          </div>
          <button className="btn btn-outline">
            <span>📊</span>
            Exporter
          </button>
          <button 
            className="btn btn-primary"
            onClick={() => {
              resetNewEvent();
              setShowCreateModal(true);
            }}
          >
            <span>➕</span>
            Nouvel Événement
          </button>
        </div>
      </div>

      {/* Contenu principal selon le mode de vue */}
      {viewMode === 'list' ? (
        <div className="events-table-container">
          <table className="events-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Événement</th>
                <th>Lieu</th>
                <th>Organisateur</th>
                <th>Statut</th>
                <th className="actions-cell">Actions</th>
              </tr>
            </thead>
            <tbody>
              {currentEvents.map(event => (
                <tr key={event.id}>
                  <td>
                    <div className="table-date-compact">
                      <span className="date-day">{event.date.getDate().toString().padStart(2, '0')}</span>
                      <div className="date-info">
                        <span className="date-month">{event.date.toLocaleDateString('fr-FR', { month: 'short' })}</span>
                        <span className="date-time">{event.date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span className="event-title-compact">{event.title}</span>
                  </td>
                  <td>
                    <div className="table-location">
                      <span>📍</span> {event.location}
                    </div>
                  </td>
                  <td>
                    <span className="organizer-name">{event.organizer}</span>
                  </td>
                  <td>
                    <span className={`status-badge ${isUpcoming(event.date) ? 'upcoming' : 'past'}`}>
                      {isUpcoming(event.date) ? 'À venir' : 'Terminé'}
                    </span>
                  </td>
                  <td className="actions-cell">
                    <div className="table-actions">
                      <button 
                        className="btn-icon"
                        onClick={() => {
                          setSelectedEvent(event);
                          setShowEventModal(true);
                        }}
                        title="Voir détails"
                      >
                        👁️
                      </button>
                      <button 
                        className="btn-icon"
                        onClick={() => handleEditEvent(event)}
                        title="Modifier"
                      >
                        ✏️
                      </button>
                      <button 
                        className="btn-icon danger"
                        onClick={() => handleDeleteEvent(event.id)}
                        title="Supprimer"
                      >
                        🗑️
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <Pagination 
            currentPage={currentPage}
            totalItems={filteredEvents.length}
            itemsPerPage={itemsPerPage}
            onPageChange={setCurrentPage}
          />
        </div>
      ) : (
        <div className="calendar-view-container">
          {/* Calendar Grid implementation */}
          <div className="calendar-grid-wrapper">
            <div className="calendar-grid-header">
              {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map(day => (
                <div key={day} className="calendar-day-label">{day}</div>
              ))}
            </div>
            <div className="calendar-grid">
              {(() => {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth();
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                
                // Adjust for Monday start (JS getDay is 0 for Sunday)
                const startOffset = firstDay === 0 ? 6 : firstDay - 1;
                
                const cells = [];
                // Empty cells at start
                for (let i = 0; i < startOffset; i++) {
                  cells.push(<div key={`empty-${i}`} className="calendar-cell empty"></div>);
                }
                
                // Day cells
                for (let day = 1; day <= daysInMonth; day++) {
                  const currentDate = new Date(year, month, day);
                  const dayEvents = events.filter(e => 
                    e.date.getDate() === day && 
                    e.date.getMonth() === month && 
                    e.date.getFullYear() === year
                  );
                  
                  cells.push(
                    <div key={day} className={`calendar-cell ${day === now.getDate() ? 'today' : ''}`}>
                      <span className="day-number">{day}</span>
                      <div className="day-events">
                        {dayEvents.map(event => (
                          <div 
                            key={event.id} 
                            className={`event-marker ${isUpcoming(event.date) ? 'upcoming' : 'past'}`}
                            onClick={(e) => {
                              e.stopPropagation();
                              setSelectedEvent(event);
                              setShowEventModal(true);
                            }}
                            title={event.title}
                          >
                            {event.title}
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                }
                return cells;
              })()}
            </div>
          </div>
        </div>
      )}

      {filteredEvents.length === 0 && viewMode === 'list' && (
        <div className="empty-state">
          <div className="empty-icon">📅</div>
          <h3>Aucun événement trouvé</h3>
          <p>Aucun événement ne correspond à vos critères de recherche.</p>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Créer un événement
          </button>
        </div>
      )}

      {/* Modal de création d'événement */}
      {showCreateModal && (
        <div className="modal-overlay" onClick={() => {
          setShowCreateModal(false);
          resetNewEvent();
        }}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{ (newEvent as any).id ? 'Modifier l\'Événement' : 'Créer un Nouvel Événement' }</h2>
              <button 
                className="modal-close"
                onClick={() => {
                  setShowCreateModal(false);
                  resetNewEvent();
                }}
              >
                ✕
              </button>
            </div>
            
            <form onSubmit={(e) => {
              e.preventDefault();
              handleSaveEvent();
            }}>
              <div className="form-group">
                <label>Titre de l'événement *</label>
                <input
                  type="text"
                  value={newEvent.title}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, title: e.target.value }))}
                  placeholder="Ex: Réunion des parents"
                  required
                />
              </div>
              
              <div className="form-group">
                <label>Description *</label>
                <textarea
                  value={newEvent.description}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, description: e.target.value }))}
                  placeholder="Décrivez l'événement..."
                  rows={4}
                  required
                />
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label>Date et heure *</label>
                  <input
                    type="datetime-local"
                    value={newEvent.date.toISOString().slice(0, 16)}
                    onChange={(e) => setNewEvent(prev => ({ ...prev, date: new Date(e.target.value) }))}
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label>Lieu *</label>
                  <input
                    type="text"
                    value={newEvent.location}
                    onChange={(e) => setNewEvent(prev => ({ ...prev, location: e.target.value }))}
                    placeholder="Ex: Salle de conférence"
                    required
                  />
                </div>
              </div>
              
              <div className="form-group">
                <label>Organisateur *</label>
                <input
                  type="text"
                  value={newEvent.organizer}
                  onChange={(e) => setNewEvent(prev => ({ ...prev, organizer: e.target.value }))}
                  placeholder="Ex: Direction Pédagogique"
                  required
                />
              </div>
              
              {/* Section Média (optionnelle) */}
              <div className="form-group">
                <label>Médias (optionnel)</label>
                <div className="media-upload-section">
                  <input
                    type="file"
                    id="media-upload"
                    multiple
                    accept="image/*,video/*,.pdf,.doc,.docx"
                    onChange={(e) => handleMediaUpload(e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="media-upload" className="upload-button">
                    <span>📎</span>
                    Ajouter des fichiers
                  </label>
                  <span className="upload-hint">Images, vidéos ou documents</span>
                </div>
                
                {/* Aperçu des médias */}
                {newEvent.media && newEvent.media.length > 0 && (
                  <div className="media-preview">
                    <h4>Fichiers attachés :</h4>
                    <div className="media-list">
                      {newEvent.media.map((media, index) => (
                        <div key={index} className="media-item">
                          <div className="media-info">
                            <span className="media-icon">{getMediaIcon(media.type)}</span>
                            <div className="media-details">
                              <span className="media-name">{media.name}</span>
                              <span className="media-size">{formatFileSize(media.size)}</span>
                            </div>
                          </div>
                          <button
                            type="button"
                            className="remove-media"
                            onClick={() => handleRemoveMedia(index)}
                            title="Supprimer ce fichier"
                          >
                            ✕
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
              
              <div className="modal-actions">
                <button 
                  type="button" 
                  className="btn btn-outline"
                  onClick={() => setShowCreateModal(false)}
                >
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {(newEvent as any).id ? 'Enregistrer les modifications' : 'Créer l\'Événement'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de détails d'événement */}
      {showEventModal && selectedEvent && (
        <div className="modal-overlay" onClick={() => setShowEventModal(false)}>
          <div className="modal-content event-details-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{selectedEvent.title}</h2>
              <button 
                className="modal-close"
                onClick={() => setShowEventModal(false)}
              >
                ✕
              </button>
            </div>
            
            <div className="event-details-content">
              <div className="event-meta">
                <span className={`status-badge ${isUpcoming(selectedEvent.date) ? 'upcoming' : 'past'}`}>
                  {isUpcoming(selectedEvent.date) ? 'À venir' : 'Terminé'}
                </span>
              </div>
              
              <div className="event-info">
                <div className="info-item">
                  <span className="info-icon">📅</span>
                  <div className="info-content">
                    <span className="info-label">Date et heure</span>
                    <span className="info-value">{formatDate(selectedEvent.date)}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📍</span>
                  <div className="info-content">
                    <span className="info-label">Lieu</span>
                    <span className="info-value">{selectedEvent.location}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">👤</span>
                  <div className="info-content">
                    <span className="info-label">Organisateur</span>
                    <span className="info-value">{selectedEvent.organizer}</span>
                  </div>
                </div>
              </div>
              
              <div className="event-description-full">
                <h4>Description</h4>
                <p>{selectedEvent.description}</p>
              </div>
              
              {/* Section Médias */}
              {selectedEvent.media && selectedEvent.media.length > 0 && (
                <div className="event-media-section">
                  <h4>Médias attachés</h4>
                  <div className="media-grid">
                    {selectedEvent.media.map((media, index) => (
                      <div key={index} className="media-card">
                        <div className="media-preview">
                          {media.type === 'image' ? (
                            <img 
                              src={media.url} 
                              alt={media.name}
                              className="media-thumbnail"
                            />
                          ) : (
                            <div className="media-placeholder">
                              <span className="media-icon-large">{getMediaIcon(media.type)}</span>
                            </div>
                          )}
                        </div>
                        <div className="media-info">
                          <span className="media-name" title={media.name}>{media.name}</span>
                          <span className="media-size">{formatFileSize(media.size)}</span>
                          <button 
                            className="btn btn-sm btn-outline"
                            onClick={() => window.open(media.url, '_blank')}
                          >
                            <span>🔗</span>
                            Ouvrir
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
            
            <div className="modal-actions">
              <button 
                className="btn btn-outline"
                onClick={() => {
                  setShowEventModal(false);
                  handleEditEvent(selectedEvent);
                }}
              >
                <span>✏️</span>
                Modifier
              </button>
              <button 
                className="btn btn-danger"
                onClick={() => {
                  handleDeleteEvent(selectedEvent.id);
                  setShowEventModal(false);
                }}
              >
                <span>🗑️</span>
                Supprimer
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

(EventsPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default EventsPage;