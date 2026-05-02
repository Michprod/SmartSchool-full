import React, { useState, useRef } from 'react';
import type { ChangeEvent } from 'react';
import './PhotoUpload.css';

export interface PhotoUploadProps {
  currentPhoto?: string;
  onPhotoChange: (file: File, preview: string) => void;
  onPhotoRemove?: () => void;
  maxSizeMB?: number;
  label?: string;
  shape?: 'circle' | 'square';
  required?: boolean;
  disabled?: boolean;
}

const PhotoUpload: React.FC<PhotoUploadProps> = ({
  currentPhoto,
  onPhotoChange,
  onPhotoRemove,
  maxSizeMB = 5,
  label = 'Photo de profil',
  shape = 'circle',
  required = false,
  disabled = false
}) => {
  const [preview, setPreview] = useState<string>(currentPhoto || '');
  const [error, setError] = useState<string>('');
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const validateFile = (file: File): string | null => {
    // Check file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
      return 'Format invalide. Utilisez JPG, PNG ou WEBP.';
    }

    // Check file size
    const maxSize = maxSizeMB * 1024 * 1024; // Convert MB to bytes
    if (file.size > maxSize) {
      return `La photo ne doit pas dépasser ${maxSizeMB} MB.`;
    }

    return null;
  };

  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    processFile(file);
  };

  const processFile = (file: File) => {
    setError('');

    const validationError = validateFile(file);
    if (validationError) {
      setError(validationError);
      return;
    }

    // Create preview
    const reader = new FileReader();
    reader.onloadend = () => {
      const result = reader.result as string;
      setPreview(result);
      onPhotoChange(file, result);
    };
    reader.readAsDataURL(file);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const file = e.dataTransfer.files[0];
    if (file) {
      processFile(file);
    }
  };

  const handleClick = () => {
    fileInputRef.current?.click();
  };

  const handleRemove = () => {
    setPreview('');
    setError('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    onPhotoRemove?.();
  };

  return (
    <div className={`photo-upload ${disabled ? 'disabled' : ''}`}>
      <label className="photo-upload-label">
        {label}
        {required && <span className="required">*</span>}
      </label>

      <div
        className={`photo-upload-container ${shape} ${isDragging ? 'dragging' : ''} ${disabled ? 'disabled' : ''}`}
        onDragOver={disabled ? undefined : handleDragOver}
        onDragLeave={disabled ? undefined : handleDragLeave}
        onDrop={disabled ? undefined : handleDrop}
        onClick={disabled ? undefined : handleClick}
        style={disabled ? { cursor: 'not-allowed', opacity: 0.6 } : {}}
      >
        {preview ? (
          <div className="photo-preview">
            <img src={preview} alt="Preview" className={shape} />
            <div className="photo-overlay">
              <button
                type="button"
                className="photo-remove-btn"
                onClick={(e) => {
                  e.stopPropagation();
                  handleRemove();
                }}
                title="Supprimer la photo"
              >
                🗑️
              </button>
              <button
                type="button"
                className="photo-change-btn"
                onClick={handleClick}
                title="Changer la photo"
              >
                📷
              </button>
            </div>
          </div>
        ) : (
          <div className="photo-placeholder">
            <span className="photo-icon">📷</span>
            <p>Cliquez ou glissez-déposez</p>
            <small>JPG, PNG ou WEBP (max {maxSizeMB} MB)</small>
          </div>
        )}
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/jpeg,image/jpg,image/png,image/webp"
        onChange={handleFileChange}
        className="photo-input-hidden"
      />

      {error && <div className="photo-error">{error}</div>}
    </div>
  );
};

export default PhotoUpload;
