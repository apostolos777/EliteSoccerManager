<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Edit Player Profile</h3>
                </div>
                <div class="card-body">

                    <!-- Alerts -->
                    <div id="alert-container"></div>

                    <form id="player-profile-form" enctype="multipart/form-data">
                        @csrf

                        <!-- Profile Picture Section -->
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <div class="profile-picture-container mb-3">
                                    <div id="profile-picture-preview"
                                        class="bg-light rounded border d-flex align-items-center justify-content-center"
                                        style="width: 200px; height: 200px;">
                                        <i class="fas fa-user fa-5x text-muted"></i>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <input type="file"
                                        id="profile_picture"
                                        name="profile_picture"
                                        accept="image/jpeg,image/png,image/jpg,image/gif"
                                        class="form-control"
                                        onchange="previewProfilePicture(this)">
                                    <small class="text-muted">JPG, PNG (Max 5MB)</small>
                                </div>
                                <button type="button"
                                    class="btn btn-sm btn-primary"
                                    onclick="uploadProfilePicture()">
                                    Upload Photo
                                </button>
                            </div>

                            <!-- Basic Information -->
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Player Name *</label>
                                    <input type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        value="Sample Player"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date"
                                        class="form-control"
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        value="">
                                </div>

                                <div class="mb-3">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <div class="input-group">
                                        <select class="form-select" id="nationality" name="nationality">
                                            <option value="">Select Country</option>
                                            <option value="South Africa">🇿🇦 South Africa</option>
                                            <option value="United Kingdom">🇬🇧 United Kingdom</option>
                                            <option value="Australia">🇦🇺 Australia</option>
                                            <option value="Brazil">🇧🇷 Brazil</option>
                                            <option value="France">🇫🇷 France</option>
                                            <option value="Germany">🇩🇪 Germany</option>
                                            <option value="Spain">🇪🇸 Spain</option>
                                            <option value="Netherlands">🇳🇱 Netherlands</option>
                                            <option value="Italy">🇮🇹 Italy</option>
                                            <option value="United States">🇺🇸 United States</option>
                                        </select>
                                    </div>
                                    <small class="text-muted">Country flag displays with selection</small>
                                </div>

                                <div class="mb-3">
                                    <label for="school" class="form-label">School</label>
                                    <input type="text"
                                        class="form-control"
                                        id="school"
                                        name="school"
                                        value=""
                                        placeholder="Example: Curro Hermanus">
                                    <small class="text-muted">Example: Curro Hermanus</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Teams Section -->
                        <div class="mb-4">
                            <h5 class="card-subtitle mb-3">Teams</h5>
                            <div class="mb-3">
                                <label for="teams" class="form-label">Select Teams *</label>
                                <select class="form-control select2"
                                    id="teams"
                                    name="teams[]"
                                    multiple="multiple"
                                    style="width: 100%;">
                                    <option value="1">Team One</option>
                                    <option value="2">Team Two</option>
                                    <option value="3">Team Three</option>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple teams</small>
                            </div>
                            <div id="selected-teams-display" class="mb-3">
                            </div>
                        </div>

                        <hr>

                        <!-- Football Information Section -->
                        <div class="mb-4">
                            <h5 class="card-subtitle mb-3">Football Information</h5>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="primary_position" class="form-label">Primary Position</label>
                                        <select class="form-select" id="primary_position" name="primary_position">
                                            <option value="">Select Position</option>
                                            <option value="GK">Goalkeeper (GK)</option>
                                            <option value="RB">Right Back (RB)</option>
                                            <option value="CB">Center Back (CB)</option>
                                            <option value="LB">Left Back (LB)</option>
                                            <option value="RM">Right Midfielder (RM)</option>
                                            <option value="CM">Center Midfielder (CM)</option>
                                            <option value="LM">Left Midfielder (LM)</option>
                                            <option value="CAM">Center Attacking Midfielder (CAM)</option>
                                            <option value="CF">Center Forward (CF)</option>
                                            <option value="ST">Striker (ST)</option>
                                            <option value="RW">Right Wing (RW)</option>
                                            <option value="LW">Left Wing (LW)</option>
                                            <option value="RF">Right Forward (RF)</option>
                                            <option value="LF">Left Forward (LF)</option>
                                            <option value="WB">Wing Back (WB)</option>
                                            <option value="DM">Defensive Midfielder (DM)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="secondary_position" class="form-label">Secondary Position</label>
                                        <select class="form-select" id="secondary_position" name="secondary_position">
                                            <option value="">Select Position</option>
                                            <option value="GK">Goalkeeper (GK)</option>
                                            <option value="RB">Right Back (RB)</option>
                                            <option value="CB">Center Back (CB)</option>
                                            <option value="LB">Left Back (LB)</option>
                                            <option value="RM">Right Midfielder (RM)</option>
                                            <option value="CM">Center Midfielder (CM)</option>
                                            <option value="LM">Left Midfielder (LM)</option>
                                            <option value="CAM">Center Attacking Midfielder (CAM)</option>
                                            <option value="CF">Center Forward (CF)</option>
                                            <option value="ST">Striker (ST)</option>
                                            <option value="RW">Right Wing (RW)</option>
                                            <option value="LW">Left Wing (LW)</option>
                                            <option value="RF">Right Forward (RF)</option>
                                            <option value="LF">Left Forward (LF)</option>
                                            <option value="WB">Wing Back (WB)</option>
                                            <option value="DM">Defensive Midfielder (DM)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="third_position" class="form-label">Third Position</label>
                                        <select class="form-select" id="third_position" name="third_position">
                                            <option value="">Select Position</option>
                                            <option value="GK">Goalkeeper (GK)</option>
                                            <option value="RB">Right Back (RB)</option>
                                            <option value="CB">Center Back (CB)</option>
                                            <option value="LB">Left Back (LB)</option>
                                            <option value="RM">Right Midfielder (RM)</option>
                                            <option value="CM">Center Midfielder (CM)</option>
                                            <option value="LM">Left Midfielder (LM)</option>
                                            <option value="CAM">Center Attacking Midfielder (CAM)</option>
                                            <option value="CF">Center Forward (CF)</option>
                                            <option value="ST">Striker (ST)</option>
                                            <option value="RW">Right Wing (RW)</option>
                                            <option value="LW">Left Wing (LW)</option>
                                            <option value="RF">Right Forward (RF)</option>
                                            <option value="LF">Left Forward (LF)</option>
                                            <option value="WB">Wing Back (WB)</option>
                                            <option value="DM">Defensive Midfielder (DM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="jersey_number" class="form-label">Jersey Number</label>
                                        <input type="number"
                                            class="form-control"
                                            id="jersey_number"
                                            name="jersey_number"
                                            min="1"
                                            max="99"
                                            value="">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="height" class="form-label">Height (cm)</label>
                                        <input type="text"
                                            class="form-control"
                                            id="height"
                                            name="height"
                                            value="">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="text"
                                            class="form-control"
                                            id="weight"
                                            name="weight"
                                            value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Personal Details Section -->
                        <div class="mb-4">
                            <h5 class="card-subtitle mb-3">Personal Details</h5>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel"
                                    class="form-control"
                                    id="phone"
                                    name="phone"
                                    value="">
                            </div>

                            <div class="mb-3">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                <input type="text"
                                    class="form-control"
                                    id="emergency_contact_name"
                                    name="emergency_contact_name"
                                    value=""
                                    placeholder="Example: Jane Doe">
                                <small class="text-muted">Example: Jane Doe – 082 123 4567</small>
                            </div>

                            <div class="mb-3">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="tel"
                                    class="form-control"
                                    id="emergency_contact_phone"
                                    name="emergency_contact_phone"
                                    value=""
                                    placeholder="082 123 4567">
                            </div>

                            <div class="mb-3">
                                <label for="medical_notes" class="form-label">Medical Notes</label>
                                <textarea class="form-control"
                                    id="medical_notes"
                                    name="medical_notes"
                                    rows="3"
                                    placeholder="Example: Asthma, carries inhaler"></textarea>
                                <small class="text-muted">Example: Asthma, carries inhaler</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Parent/Guardian Information -->
                        <div class="mb-4">
                            <h5 class="card-subtitle mb-3">Parent/Guardian Information</h5>

                            <div class="mb-3">
                                <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                                <input type="text"
                                    class="form-control"
                                    id="parent_name"
                                    name="parent_name"
                                    value="">
                            </div>

                            <div class="mb-3">
                                <label for="parent_email" class="form-label">Parent/Guardian Email</label>
                                <input type="email"
                                    class="form-control"
                                    id="parent_email"
                                    name="parent_email"
                                    value="">
                            </div>

                            <div class="mb-3">
                                <label for="parent_phone" class="form-label">Parent/Guardian Phone</label>
                                <input type="tel"
                                    class="form-control"
                                    id="parent_phone"
                                    name="parent_phone"
                                    value="">
                            </div>
                        </div>

                        <hr>

                        <!-- Documents Section -->
                        <div class="mb-4">
                            <h5 class="card-subtitle mb-3">Upload Documents</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload ID / License</label>
                                        <div class="input-group">
                                            <input type="file"
                                                class="form-control"
                                                accept="application/pdf,image/jpeg,image/png,image/jpg"
                                                id="id_document">
                                            <button class="btn btn-outline-primary"
                                                type="button"
                                                onclick="uploadDocument('id')">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </div>
                                        <small class="text-muted">PDF, JPG, PNG (Max 10MB)</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Passport</label>
                                        <div class="input-group">
                                            <input type="file"
                                                class="form-control"
                                                accept="application/pdf,image/jpeg,image/png,image/jpg"
                                                id="passport_document">
                                            <button class="btn btn-outline-primary"
                                                type="button"
                                                onclick="uploadDocument('passport')">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </div>
                                        <small class="text-muted">PDF, JPG, PNG (Max 10MB)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Upload Birth Certificate</label>
                                        <div class="input-group">
                                            <input type="file"
                                                class="form-control"
                                                accept="application/pdf,image/jpeg,image/png,image/jpg"
                                                id="birth_certificate_document">
                                            <button class="btn btn-outline-primary"
                                                type="button"
                                                onclick="uploadDocument('birth_certificate')">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </div>
                                        <small class="text-muted">PDF, JPG, PNG (Max 10MB)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents List -->
                            <div class="mt-4">
                                <h6>Uploaded Documents</h6>
                                <div id="documents-list" class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Document Type</th>
                                                <th>File Size</th>
                                                <th>Uploaded</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="documents-tbody">
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="javascript:history.back()"
                                class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery for Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const playerId = 1; // Set this from actual player data

    // Initialize Select2 for teams
    $(document).ready(function () {
        $('#teams').select2({
            placeholder: 'Select one or more teams',
            allowClear: true,
        });

        // Load documents on page load
        loadDocuments();
    });

    // Preview profile picture before upload
    function previewProfilePicture(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewElement = document.getElementById('profile-picture-preview');
                
                // If preview is a div (placeholder), replace it with img
                if (previewElement.tagName === 'DIV') {
                    const img = document.createElement('img');
                    img.id = 'profile-picture-preview';
                    img.src = e.target.result;
                    img.alt = 'Profile Picture';
                    img.className = 'img-fluid rounded border';
                    img.style.maxWidth = '200px';
                    previewElement.replaceWith(img);
                } else {
                    // If it's already an img, just update src
                    previewElement.src = e.target.result;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Upload profile picture
    function uploadProfilePicture() {
        const fileInput = document.getElementById('profile_picture');
        if (!fileInput.files[0]) {
            showAlert('Please select a file', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', fileInput.files[0]);
        formData.append('_token', document.querySelector('[name="_token"]').value || 'demo-token');

        fetch(`/player/${playerId}/upload-profile-picture`, {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    const previewElement = document.getElementById('profile-picture-preview');
                    if (previewElement.tagName === 'IMG') {
                        previewElement.src = data.profile_picture_url;
                    }
                    fileInput.value = '';
                } else {
                    showAlert(data.message || 'Error uploading profile picture', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }

    // Upload document
    function uploadDocument(docType) {
        const inputId = docType.replace(/_/g, '') + '_document';
        const fileInput = document.getElementById(inputId);
        if (!fileInput.files[0]) {
            showAlert('Please select a file', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('document_type', docType);
        formData.append('document_file', fileInput.files[0]);
        formData.append('_token', document.querySelector('[name="_token"]').value || 'demo-token');

        fetch(`/player/${playerId}/upload-document`, {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    fileInput.value = '';
                    loadDocuments();
                } else {
                    showAlert(data.message || 'Error uploading document', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }

    // Load documents list
    function loadDocuments() {
        fetch(`/player/${playerId}/documents`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('documents-tbody');
                    tbody.innerHTML = '';

                    if (data.documents.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No documents uploaded</td></tr>';
                    } else {
                        data.documents.forEach(doc => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                        <td>${doc.type_label}</td>
                        <td>${doc.file_size}</td>
                        <td>${doc.uploaded_at}</td>
                        <td>
                            <a href="${doc.download_url}" class="btn btn-sm btn-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    `;
                            tbody.appendChild(row);
                        });
                    }
                }
            });
    }

    // Delete document
    function deleteDocument(docId) {
        if (!confirm('Are you sure you want to delete this document?')) return;

        fetch(`/player/${playerId}/document/${docId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value || 'demo-token',
                'Content-Type': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadDocuments();
                } else {
                    showAlert(data.message || 'Error deleting document', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }

    // Handle form submission (send FormData so arrays like teams[] are preserved)
    document.getElementById('player-profile-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch(`/player/${playerId}/update-profile`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value || 'demo-token',
            },
            body: formData,
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                } else {
                    showAlert(result.message || 'Error updating profile', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    });

    // Show alert
    function showAlert(message, type = 'info') {
        const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
        document.getElementById('alert-container').innerHTML = alertHtml;
    }
</script>
</body>
</html>
