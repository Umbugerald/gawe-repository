/* =============================================
   SC1 Alert - Custom Alert & Confirm Component
   =============================================
   Usage:
     await SC1Alert.show('Berhasil!', 'success');
     await SC1Alert.show('Gagal!', 'error');
     await SC1Alert.show('Peringatan!', 'warning');
     await SC1Alert.show('Info', 'info');
     const ok = await SC1Alert.confirm('Yakin?');
   ============================================= */

const SC1Alert = (() => {

    // SVG Icons for each type
    const ICONS = {
        success: `<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>`,
        error: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        warning: `<svg viewBox="0 0 24 24"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        info: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        confirm: `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
    };

    /**
     * Internal: Create and show the dialog
     * @param {string} message - The message to display
     * @param {string} type - 'success' | 'error' | 'warning' | 'info' | 'confirm'
     * @param {boolean} isConfirm - Whether this is a confirm dialog
     * @returns {Promise<boolean>}
     */
    function _createDialog(message, type, isConfirm) {
        return new Promise((resolve) => {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'sc1-alert-overlay';

            // Build buttons
            let buttonsHTML = '';
            if (isConfirm) {
                buttonsHTML = `
                    <button class="sc1-alert-btn sc1-alert-btn-cancel" data-action="cancel">Batal</button>
                    <button class="sc1-alert-btn sc1-alert-btn-primary" data-action="ok">Ya, Lanjutkan</button>
                `;
            } else {
                buttonsHTML = `
                    <button class="sc1-alert-btn sc1-alert-btn-primary" data-action="ok">OK</button>
                `;
            }

            // Build modal HTML
            overlay.innerHTML = `
                <div class="sc1-alert-box sc1-type-${type}">
                    <div class="sc1-alert-icon">
                        ${ICONS[type] || ICONS.info}
                    </div>
                    <p class="sc1-alert-message">${message}</p>
                    <div class="sc1-alert-buttons">
                        ${buttonsHTML}
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            // Trigger entrance animation (next frame)
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    overlay.classList.add('sc1-active');
                });
            });

            // Close function
            function closeDialog(result) {
                overlay.classList.remove('sc1-active');
                overlay.classList.add('sc1-closing');
                setTimeout(() => {
                    overlay.remove();
                    resolve(result);
                }, 250);
            }

            // Button click handlers
            overlay.querySelectorAll('.sc1-alert-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action');
                    closeDialog(action === 'ok');
                });
            });

            // Click overlay to cancel (only for confirm) or close (for alert)
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeDialog(isConfirm ? false : true);
                }
            });

            // Keyboard support
            function handleKeydown(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.removeEventListener('keydown', handleKeydown);
                    closeDialog(true);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    document.removeEventListener('keydown', handleKeydown);
                    closeDialog(isConfirm ? false : true);
                }
            }
            document.addEventListener('keydown', handleKeydown);

            // Focus the primary button
            const primaryBtn = overlay.querySelector('.sc1-alert-btn-primary');
            if (primaryBtn) primaryBtn.focus();
        });
    }

    return {
        /**
         * Show an alert dialog (replacement for alert())
         * @param {string} message - Message to display
         * @param {string} [type='info'] - 'success' | 'error' | 'warning' | 'info'
         * @returns {Promise<true>}
         */
        show(message, type = 'info') {
            return _createDialog(message, type, false);
        },

        /**
         * Show a confirm dialog (replacement for confirm())
         * @param {string} message - Message to display
         * @returns {Promise<boolean>} - true if confirmed, false if cancelled
         */
        confirm(message) {
            return _createDialog(message, 'confirm', true);
        }
    };

})();
