import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import type { InventoryItem } from '../types';
import DashboardLayout from '../../../Core/Layouts/DashboardLayout';
import Pagination from '../../../Core/Components/Pagination';
import './InventoryPage.css';
import axios from 'axios';

const InventoryPage: React.FC = () => {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [selectedStatus, setSelectedStatus] = useState<string>('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);
  const [showItemModal, setShowItemModal] = useState(false);
  const [newItem, setNewItem] = useState<Partial<InventoryItem>>({
    name: '',
    category: '',
    quantity: 0,
    location: '',
    status: 'in_stock'
  });

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  useEffect(() => {
    setCurrentPage(1);
  }, [searchTerm, selectedCategory, selectedStatus]);

  const categories = [
    'Mobilier',
    'Informatique',
    'Matériel Pédagogique',
    'Équipement Sportif',
    'Fournitures',
    'Électronique',
    'Livres',
    'Laboratoire'
  ];

  const loadInventory = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/inventory');
      // Backend returns either an array or paginated object, robust check:
      const data = response.data?.data || response.data || [];
      setItems(Array.isArray(data) ? data : []);
    } catch (error) {
      console.error('Error loading inventory:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadInventory();
  }, []);

  const filteredItems = (items || []).filter(item => {
    if (!item) return false;
    const name = item.name || '';
    const category = item.category || '';
    const location = item.location || '';
    const status = item.status || '';

    const matchesSearch = name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         category.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         location.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
    const matchesStatus = selectedStatus === 'all' || status === selectedStatus;
    
    return matchesSearch && matchesCategory && matchesStatus;
  });

  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentItems = filteredItems.slice(indexOfFirstItem, indexOfLastItem);

  const getStatusStats = () => {
    return {
      total: items.length,
      inStock: items.filter(item => item.status === 'in_stock').length,
      inUse: items.filter(item => item.status === 'in_use').length,
      maintenance: items.filter(item => item.status === 'under_maintenance').length,
      totalQuantity: items.reduce((sum, item) => sum + item.quantity, 0)
    };
  };

  const handleCreateItem = async () => {
    if (newItem.name && newItem.category && newItem.location && newItem.quantity !== undefined) {
      try {
        const itemPayload = {
          name: newItem.name,
          category: newItem.category,
          quantity: newItem.quantity,
          location: newItem.location,
          status: newItem.status || 'in_stock'
        };
        const response = await axios.post('/api/inventory', itemPayload);
        setItems([...items, response.data]);
        setNewItem({
          name: '',
          category: '',
          quantity: 0,
          location: '',
          status: 'in_stock'
        });
        setShowCreateModal(false);
      } catch (e: any) {
        console.error('Failed to create inventory item', e);
        alert("Erreur lors de la création.");
      }
    }
  };

  const handleEditItem = (item: InventoryItem) => {
    setEditingItem(item);
    setNewItem(item);
    setShowCreateModal(true);
  };

  const handleUpdateItem = async () => {
    if (editingItem && newItem.name && newItem.category && newItem.location && newItem.quantity !== undefined) {
      try {
        const itemPayload = {
          name: newItem.name,
          category: newItem.category,
          quantity: newItem.quantity,
          location: newItem.location,
          status: newItem.status
        };
        const response = await axios.put(`/api/inventory/${editingItem.id}`, itemPayload);
        
        const updatedItems = items.map(item =>
          item.id === editingItem.id
            ? { ...item, ...response.data }
            : item
        );
        setItems(updatedItems);
        setEditingItem(null);
        setNewItem({
          name: '',
          category: '',
          quantity: 0,
          location: '',
          status: 'in_stock'
        });
        setShowCreateModal(false);
      } catch (e) {
        console.error('Failed to update inventory item', e);
        alert("Erreur lors de la mise à jour.");
      }
    }
  };

  const handleDeleteItem = async (itemId: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet élément de l\'inventaire ?')) {
      try {
        await axios.delete(`/api/inventory/${itemId}`);
        setItems(items.filter(item => item.id !== itemId));
      } catch (e) {
        console.error('Failed to delete item', e);
      }
    }
  };

  const getStatusLabel = (status: string) => {
    const labels = {
      'in_stock': 'En Stock',
      'in_use': 'En Utilisation',
      'under_maintenance': 'En Maintenance'
    };
    return labels[status as keyof typeof labels] || status;
  };

  const getStatusColor = (status: string) => {
    const colors = {
      'in_stock': 'success',
      'in_use': 'warning',
      'under_maintenance': 'danger'
    };
    return colors[status as keyof typeof colors] || 'default';
  };

  const stats = getStatusStats();

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement de l'inventaire...</p>
      </div>
    );
  }

  return (
    <div className="inventory-page">
      <Head title="Gestion de l'Inventaire" />
      <div className="page-header">
        <h1>Gestion de l'Inventaire</h1>
        <p className="page-subtitle">
          Suivi et gestion des équipements scolaires
        </p>
      </div>

      {/* Statistiques de l'inventaire */}
      <div className="inventory-stats">
        <div className="stat-card total">
          <div className="stat-icon">📦</div>
          <div className="stat-content">
            <span className="stat-number">{stats.total}</span>
            <span className="stat-label">Articles Total</span>
          </div>
        </div>
        <div className="stat-card in-stock">
          <div className="stat-icon">✅</div>
          <div className="stat-content">
            <span className="stat-number">{stats.inStock}</span>
            <span className="stat-label">En Stock</span>
          </div>
        </div>
        <div className="stat-card in-use">
          <div className="stat-icon">🔄</div>
          <div className="stat-content">
            <span className="stat-number">{stats.inUse}</span>
            <span className="stat-label">En Utilisation</span>
          </div>
        </div>
        <div className="stat-card maintenance">
          <div className="stat-icon">🔧</div>
          <div className="stat-content">
            <span className="stat-number">{stats.maintenance}</span>
            <span className="stat-label">En Maintenance</span>
          </div>
        </div>
        <div className="stat-card quantity">
          <div className="stat-icon">📊</div>
          <div className="stat-content">
            <span className="stat-number">{stats.totalQuantity}</span>
            <span className="stat-label">Quantité Totale</span>
          </div>
        </div>
      </div>

      {/* Contrôles de gestion */}
      <div className="management-controls">
        <div className="search-filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Rechercher un article..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
            <span className="search-icon">🔍</span>
          </div>
          
          <div className="filter-group">
            <label>Catégorie :</label>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="filter-select"
            >
              <option value="all">Toutes les catégories</option>
              {categories.map(category => (
                <option key={category} value={category}>{category}</option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <label>Statut :</label>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="filter-select"
            >
              <option value="all">Tous les statuts</option>
              <option value="in_stock">En Stock</option>
              <option value="in_use">En Utilisation</option>
              <option value="under_maintenance">En Maintenance</option>
            </select>
          </div>
        </div>
        
        <div className="action-buttons">
          <button className="btn btn-outline">
            <span>📊</span>
            Rapport
          </button>
          <button className="btn btn-outline">
            <span>📤</span>
            Exporter
          </button>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Ajouter Article
          </button>
        </div>
      </div>

      {/* Liste des articles */}
      <div className="inventory-table-container">
        <table className="inventory-table">
          <thead>
            <tr>
              <th>Article</th>
              <th>Catégorie</th>
              <th>Emplacement</th>
              <th>Quantité</th>
              <th>Statut</th>
              <th className="actions-cell">Actions</th>
            </tr>
          </thead>
          <tbody>
            {currentItems.map(item => (
              <tr key={item.id}>
                <td>
                  <span className="item-name">{item.name}</span>
                </td>
                <td>
                  <span className="category-badge">{item.category}</span>
                </td>
                <td>
                  <div className="table-location">
                    <span>📍</span> {item.location}
                  </div>
                </td>
                <td>
                  <span className="quantity-badge">{item.quantity}</span>
                </td>
                <td>
                  <span className={`status-badge ${getStatusColor(item.status)}`}>
                    {getStatusLabel(item.status)}
                  </span>
                </td>
                <td className="actions-cell">
                  <div className="table-actions">
                    <button 
                      className="btn-icon"
                      onClick={() => {
                        setSelectedItem(item);
                        setShowItemModal(true);
                      }}
                      title="Voir détails"
                    >
                      👁️
                    </button>
                    <button 
                      className="btn-icon"
                      onClick={() => handleEditItem(item)}
                      title="Modifier"
                    >
                      ✏️
                    </button>
                    <button 
                      className="btn-icon danger"
                      onClick={() => handleDeleteItem(item.id)}
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
          totalItems={filteredItems.length}
          itemsPerPage={itemsPerPage}
          onPageChange={setCurrentPage}
        />
      </div>

      {filteredItems.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">📦</div>
          <h3>Aucun article trouvé</h3>
          <p>Aucun article ne correspond à vos critères de recherche.</p>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateModal(true)}
          >
            <span>➕</span>
            Ajouter un article
          </button>
        </div>
      )}

      {/* Modal de création/modification */}
      {showCreateModal && (
        <div className="modal-overlay" onClick={() => setShowCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{editingItem ? 'Modifier l\'article' : 'Nouvel Article'}</h2>
              <button 
                className="modal-close"
                onClick={() => {
                  setShowCreateModal(false);
                  setEditingItem(null);
                  setNewItem({
                    name: '',
                    category: '',
                    quantity: 0,
                    location: '',
                    status: 'in_stock'
                  });
                }}
              >
                ✕
              </button>
            </div>
            
            <form className="item-form" onSubmit={(e) => {
              e.preventDefault();
              if (editingItem) {
                handleUpdateItem();
              } else {
                handleCreateItem();
              }
            }}>
              <div className="form-group">
                <label htmlFor="name">Nom de l'article *</label>
                <input
                  type="text"
                  id="name"
                  value={newItem.name || ''}
                  onChange={(e) => setNewItem(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="Ex: Ordinateurs portables"
                  required
                />
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="category">Catégorie *</label>
                  <select
                    id="category"
                    value={newItem.category || ''}
                    onChange={(e) => setNewItem(prev => ({ ...prev, category: e.target.value }))}
                    required
                  >
                    <option value="">Sélectionner une catégorie</option>
                    {categories.map(category => (
                      <option key={category} value={category}>{category}</option>
                    ))}
                  </select>
                </div>
                
                <div className="form-group">
                  <label htmlFor="quantity">Quantité *</label>
                  <input
                    type="number"
                    id="quantity"
                    value={newItem.quantity || 0}
                    onChange={(e) => setNewItem(prev => ({ ...prev, quantity: parseInt(e.target.value) || 0 }))}
                    min="0"
                    required
                  />
                </div>
              </div>
              
              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="location">Emplacement *</label>
                  <input
                    type="text"
                    id="location"
                    value={newItem.location || ''}
                    onChange={(e) => setNewItem(prev => ({ ...prev, location: e.target.value }))}
                    placeholder="Ex: Salle informatique A"
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="status">Statut *</label>
                  <select
                    id="status"
                    value={newItem.status || 'in_stock'}
                    onChange={(e) => setNewItem(prev => ({ ...prev, status: e.target.value as InventoryItem['status'] }))}
                    required
                  >
                    <option value="in_stock">En Stock</option>
                    <option value="in_use">En Utilisation</option>
                    <option value="under_maintenance">En Maintenance</option>
                  </select>
                </div>
              </div>
              
              <div className="form-actions">
                <button 
                  type="button" 
                  className="btn btn-outline"
                  onClick={() => {
                    setShowCreateModal(false);
                    setEditingItem(null);
                    setNewItem({
                      name: '',
                      category: '',
                      quantity: 0,
                      location: '',
                      status: 'in_stock'
                    });
                  }}
                >
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary">
                  {editingItem ? 'Mettre à jour' : 'Ajouter l\'article'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de détails d'article */}
      {showItemModal && selectedItem && (
        <div className="modal-overlay" onClick={() => setShowItemModal(false)}>
          <div className="modal-content item-details-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{selectedItem.name}</h2>
              <button 
                className="modal-close"
                onClick={() => setShowItemModal(false)}
              >
                ✕
              </button>
            </div>
            
            <div className="item-details-content">
              <div className="item-meta">
                <span className="category-badge">{selectedItem.category}</span>
                <span className={`status-badge ${getStatusColor(selectedItem.status)}`}>
                  {getStatusLabel(selectedItem.status)}
                </span>
              </div>
              
              <div className="item-info">
                <div className="info-item">
                  <span className="info-icon">📦</span>
                  <div className="info-content">
                    <span className="info-label">Quantité</span>
                    <span className="info-value">{selectedItem.quantity} unités</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📍</span>
                  <div className="info-content">
                    <span className="info-label">Emplacement</span>
                    <span className="info-value">{selectedItem.location}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">🏷️</span>
                  <div className="info-content">
                    <span className="info-label">Catégorie</span>
                    <span className="info-value">{selectedItem.category}</span>
                  </div>
                </div>
                
                <div className="info-item">
                  <span className="info-icon">📊</span>
                  <div className="info-content">
                    <span className="info-label">Statut</span>
                    <span className="info-value">{getStatusLabel(selectedItem.status)}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="modal-actions">
              <button 
                className="btn btn-outline"
                onClick={() => {
                  handleEditItem(selectedItem);
                  setShowItemModal(false);
                }}
              >
                <span>✏️</span>
                Modifier
              </button>
              <button 
                className="btn btn-danger"
                onClick={() => {
                  handleDeleteItem(selectedItem.id);
                  setShowItemModal(false);
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

(InventoryPage as any).layout = (page: React.ReactNode) => <DashboardLayout children={page} />;

export default InventoryPage;