/**
 * Admin Panel JavaScript
 */

(function() {
    'use strict';

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        initSidebar();
        initActiveLinks();
        initConfirmDialogs();
        initDataTables();
        initTooltips();
        initCharCounters();
        initImagePreviews();
        initAutoSave();
    });

    /**
     * Sidebar Toggle with localStorage
     */
    function initSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        if (!sidebar) return;

        // Restore saved state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }

        // Handle sidebar state changes
        sidebar.addEventListener('transitionend', () => {
            window.dispatchEvent(new Event('resize'));
        });
    }

    /**
     * Toggle Sidebar Function (called from HTML)
     */
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('adminSidebar');
        sidebar.classList.toggle('collapsed');
        
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    };

    /**
     * Active Link Highlighting
     */
    function initActiveLinks() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link').forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href)) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Confirm Delete Dialogs
     */
    function initConfirmDialogs() {
        document.querySelectorAll('form[onsubmit*="confirmDelete"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    }

    window.confirmDelete = function(message) {
        return confirm(message || 'Are you sure you want to delete this item?');
    };

    /**
     * Data Tables Enhancement
     */
    function initDataTables() {
        document.querySelectorAll('.data-table').forEach(table => {
            // Add search functionality
            const wrapper = document.createElement('div');
            wrapper.className = 'table-controls mb-3';
            
            const searchBox = document.createElement('input');
            searchBox.type = 'text';
            searchBox.className = 'form-control';
            searchBox.placeholder = 'Search table...';
            searchBox.style.maxWidth = '300px';
            
            wrapper.appendChild(searchBox);
            table.parentNode.insertBefore(wrapper, table);
            
            searchBox.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });
    }

    /**
     * Initialize Tooltips
     */
    function initTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
        }
    }

    /**
     * Character Counters for Inputs
     */
    function initCharCounters() {
        document.querySelectorAll('[data-max-length]').forEach(input => {
            const maxLength = parseInt(input.dataset.maxLength);
            const counter = document.createElement('small');
            counter.className = 'form-text text-muted char-counter';
            input.parentNode.appendChild(counter);
            
            const updateCounter = () => {
                const remaining = maxLength - input.value.length;
                counter.textContent = `${remaining} characters remaining`;
                counter.style.color = remaining < 20 ? '#dc3545' : '#6c757d';
            };
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        });
    }

    /**
     * Image Preview Before Upload
     */
    function initImagePreviews() {
        document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = input.parentNode.querySelector('.image-preview');
                    
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview mt-2';
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        preview.style.borderRadius = '8px';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        });
    }

    /**
     * Auto-save for Forms (Draft)
     */
    function initAutoSave() {
        const forms = document.querySelectorAll('form[data-autosave]');
        
        forms.forEach(form => {
            const formId = form.dataset.autosave;
            let saveTimeout;
            
            // Restore saved data
            const savedData = localStorage.getItem(`autosave_${formId}`);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input && input.type !== 'file') {
                            input.value = data[key];
                        }
                    });
                } catch (e) {
                    console.error('Failed to restore autosave data', e);
                }
            }
            
            // Save on input
            form.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    const formData = new FormData(form);
                    const data = {};
                    
                    for (let [key, value] of formData.entries()) {
                        if (typeof value === 'string') {
                            data[key] = value;
                        }
                    }
                    
                    localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
                    
                    // Show save indicator
                    showSaveIndicator();
                }, 2000);
            });
            
            // Clear on successful submit
            form.addEventListener('submit', () => {
                localStorage.removeItem(`autosave_${formId}`);
            });
        });
    }

    function showSaveIndicator() {
        let indicator = document.querySelector('.autosave-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator';
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s;
            `;
            indicator.textContent = 'Draft saved';
            document.body.appendChild(indicator);
        }
        
        indicator.style.opacity = '1';
        setTimeout(() => {
            indicator.style.opacity = '0';
        }, 2000);
    }

    /**
     * AJAX Form Submission
     */
    window.submitAjaxForm = function(form, successCallback) {
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (successCallback) successCallback(data);
            } else {
                alert(data.error || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    };

    /**
     * Auto-hide Alerts
     */
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    /**
     * Bulk Actions
     */
    window.handleBulkAction = function() {
        const action = document.getElementById('bulkAction')?.value;
        const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
        
        if (!action || checkboxes.length === 0) {
            alert('Please select items and an action');
            return false;
        }
        
        if (action === 'delete') {
            return confirm(`Are you sure you want to delete ${checkboxes.length} item(s)?`);
        }
        
        return true;
    };

})();