(function () {
    document.querySelectorAll('[data-product-image-manager]').forEach(function (manager) {
        const input = manager.querySelector('[data-image-input]');
        const grid = manager.querySelector('[data-image-grid]');
        const orderFields = manager.querySelector('[data-image-order]');
        const feedback = manager.querySelector('[data-image-feedback]');
        const errors = manager.querySelector('[data-image-errors]');
        const dropzone = manager.querySelector('[data-image-dropzone]');
        const managerState = manager.querySelector('[data-image-manager-state]');
        const maxImages = Number(manager.dataset.maxImages);
        const maxBytes = Number(manager.dataset.maxBytes);
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        const supportsFileListReorder = typeof DataTransfer !== 'undefined';
        let nextUploadId = 0;
        let fallbackHasRejectedFiles = false;

        function cards() {
            return Array.from(grid.querySelectorAll('.product-image-card'));
        }

        function sync() {
            const items = cards();
            const uploads = [];
            orderFields.innerHTML = '';
            managerState.value = '1';

            items.forEach(function (card, index) {
                const badge = card.querySelector('[data-image-badge]');
                badge.hidden = index !== 0;
                card.querySelector('[data-image-up]').disabled = index === 0;
                card.querySelector('[data-image-down]').disabled = index === items.length - 1;

                if (card.dataset.uploadId !== undefined) {
                    if (supportsFileListReorder) {
                        uploads.push(card);
                        card.dataset.token = 'new:' + (uploads.length - 1);
                        card.dataset.fileIndex = String(uploads.length - 1);
                    } else {
                        card.dataset.token = 'new:' + card.dataset.fileIndex;
                    }
                }

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'image_order[]';
                hidden.value = card.dataset.token;
                orderFields.appendChild(hidden);
            });

            if (supportsFileListReorder) {
                try {
                    const transfer = new DataTransfer();
                    uploads.forEach(function (card) {
                        transfer.items.add(card._productImageFile);
                    });
                    input.files = transfer.files;
                } catch (error) {
                    fallbackHasRejectedFiles = true;
                    errors.textContent = 'Your browser could not update the selected file order. Reload this page in a current browser before saving.';
                }
            }

            const countLabel = items.length === 1 ? 'image' : 'images';
            feedback.textContent = items.length + ' of ' + maxImages + ' ' + countLabel + ' selected. The first image is the thumbnail.';
        }

        function addControls(card) {
            const actions = document.createElement('div');
            actions.className = 'product-image-actions';

            const up = document.createElement('button');
            up.type = 'button';
            up.className = 'product-image-action';
            up.dataset.imageUp = '';
            up.setAttribute('aria-label', 'Move image earlier');
            up.title = 'Move earlier';
            up.textContent = '\u2191';

            const down = document.createElement('button');
            down.type = 'button';
            down.className = 'product-image-action';
            down.dataset.imageDown = '';
            down.setAttribute('aria-label', 'Move image later');
            down.title = 'Move later';
            down.textContent = '\u2193';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'product-image-action product-image-remove';
            remove.dataset.imageRemove = '';
            remove.setAttribute('aria-label', 'Remove image');
            remove.title = 'Remove image';
            remove.textContent = '\u00d7';

            actions.append(up, down, remove);
            card.appendChild(actions);
        }

        function makeUploadCard(file, sourceIndex) {
            const card = document.createElement('div');
            card.className = 'product-image-card';
            card.dataset.uploadId = String(nextUploadId++);
            card.dataset.fileIndex = String(sourceIndex);
            card._productImageFile = file;

            const preview = document.createElement('div');
            preview.className = 'product-image-preview';
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            preview.appendChild(image);

            const badge = document.createElement('span');
            badge.className = 'product-image-badge';
            badge.dataset.imageBadge = '';
            badge.textContent = 'Thumbnail';
            preview.appendChild(badge);
            card.appendChild(preview);

            const meta = document.createElement('div');
            meta.className = 'product-image-meta';
            meta.title = file.name;
            meta.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            card.appendChild(meta);
            addControls(card);
            return card;
        }

        function addFiles(fileList) {
            const picked = Array.from(fileList);
            const current = cards().length;
            const available = Math.max(0, maxImages - current);
            const messages = [];
            let added = 0;

            if (!supportsFileListReorder && cards().some(function (card) {
                return card.dataset.uploadId !== undefined;
            })) {
                errors.textContent = 'This browser supports one file selection at a time. Remove the current new images before selecting another batch.';
                return;
            }

            picked.forEach(function (file, sourceIndex) {
                const extensionIsAllowed = /\.(jpe?g|png|webp|gif)$/i.test(file.name);
                if (file.type ? !allowedTypes.includes(file.type.toLowerCase()) : !extensionIsAllowed) {
                    messages.push(file.name + ': unsupported image type.');
                } else if (file.size > maxBytes) {
                    messages.push(file.name + ': larger than 2MB.');
                } else if (added >= available) {
                    messages.push(file.name + ': image limit reached.');
                } else {
                    grid.appendChild(makeUploadCard(file, sourceIndex));
                    added++;
                }
            });

            errors.textContent = messages.join(' ');
            fallbackHasRejectedFiles = !supportsFileListReorder && messages.length > 0;
            sync();
        }

        cards().forEach(function (card) {
            const image = card.querySelector('img');
            const preview = document.createElement('div');
            preview.className = 'product-image-preview';
            preview.appendChild(image);

            const badge = document.createElement('span');
            badge.className = 'product-image-badge';
            badge.dataset.imageBadge = '';
            badge.textContent = 'Thumbnail';
            preview.appendChild(badge);
            card.insertBefore(preview, card.firstChild);

            const meta = document.createElement('div');
            meta.className = 'product-image-meta';
            meta.textContent = card.dataset.label || 'Current image';
            card.appendChild(meta);
            addControls(card);
        });

        input.addEventListener('change', function () {
            addFiles(input.files);
        });

        input.addEventListener('click', function (event) {
            if (!supportsFileListReorder && cards().some(function (card) {
                return card.dataset.uploadId !== undefined;
            })) {
                event.preventDefault();
                errors.textContent = 'Remove the current new images before selecting another batch in this browser.';
            }
        });

        grid.addEventListener('click', function (event) {
            const button = event.target.closest('button');
            if (!button) return;

            const card = button.closest('.product-image-card');
            if (button.hasAttribute('data-image-remove')) {
                const image = card.querySelector('img');
                if (card.dataset.uploadId !== undefined) URL.revokeObjectURL(image.src);
                card.remove();
                errors.textContent = '';
                if (!supportsFileListReorder && !cards().some(function (item) {
                    return item.dataset.uploadId !== undefined;
                })) {
                    fallbackHasRejectedFiles = false;
                    input.value = '';
                }
                sync();
                return;
            }

            if (button.hasAttribute('data-image-up') && card.previousElementSibling) {
                grid.insertBefore(card, card.previousElementSibling);
            } else if (button.hasAttribute('data-image-down') && card.nextElementSibling) {
                grid.insertBefore(card.nextElementSibling, card);
            }
            sync();
        });

        dropzone.addEventListener('dragover', function (event) {
            if (!supportsFileListReorder) return;
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });

        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('is-dragging');
        });

        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
            if (!supportsFileListReorder) {
                errors.textContent = 'Use the Add images button in this browser to select files.';
                return;
            }
            addFiles(event.dataTransfer.files);
        });

        manager.closest('form').addEventListener('submit', function (event) {
            sync();
            if (fallbackHasRejectedFiles) {
                event.preventDefault();
                errors.textContent = 'Please reselect only supported images within the remaining image limit.';
            } else if (manager.dataset.required === 'true' && cards().length === 0) {
                event.preventDefault();
                errors.textContent = 'Add at least one product image before saving.';
                input.focus();
            }
        });

        sync();
    });
}());