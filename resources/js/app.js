document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-contract-representative]').forEach((selector) => {
        const form = selector.closest('form');
        const profile = form?.querySelector('[data-representative-profile]');
        if (!profile) {
            return;
        }

        selector.addEventListener('change', () => {
            const option = selector.selectedOptions[0];
            profile.querySelectorAll('[data-representative-field]').forEach((field) => {
                const key = field.dataset.representativeField;
                const datasetKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
                field.value = option?.dataset[datasetKey] || '';
            });
        });
    });

    const identityPreviewUrls = new WeakMap();
    document.addEventListener('change', (event) => {
        const input = event.target.closest?.('[data-identity-preview-input]');
        if (!input) return;

        const preview = document.getElementById(input.dataset.previewTarget || '');
        const emptyState = preview?.parentElement?.querySelector('[data-identity-preview-empty]');
        if (!preview) return;

        const previousUrl = identityPreviewUrls.get(input);
        if (previousUrl) URL.revokeObjectURL(previousUrl);

        const file = input.files?.[0];
        if (file?.type.startsWith('image/')) {
            const previewUrl = URL.createObjectURL(file);
            identityPreviewUrls.set(input, previewUrl);
            preview.src = previewUrl;
            preview.classList.remove('hidden');
            emptyState?.classList.add('hidden');
            return;
        }

        identityPreviewUrls.delete(input);
        if (preview.dataset.originalSrc) {
            preview.src = preview.dataset.originalSrc;
            preview.classList.remove('hidden');
            emptyState?.classList.add('hidden');
        } else {
            preview.removeAttribute('src');
            preview.classList.add('hidden');
            emptyState?.classList.remove('hidden');
        }
    });

    document.querySelectorAll('[data-contract-schedule]').forEach((form) => {
        const roomInput = form.querySelector('[name="room_id"]');
        const roomAvailabilityMessage = form.querySelector('[data-room-availability-message]');
        const startInput = form.querySelector('[data-contract-start]');
        const durationInput = form.querySelector('[data-contract-duration]');
        const endInput = form.querySelector('[data-contract-end]');
        const moveInInput = form.querySelector('[data-contract-move-in]');
        const deadlineInput = form.querySelector('[data-contract-move-in-deadline]');
        const deadlineWarning = form.querySelector('[data-contract-move-in-warning]');
        const deadlineError = form.querySelector('[data-contract-deadline-error]');
        const termsConfirmed = form.querySelector('[data-move-in-terms-confirmed]');
        const termsConfirmation = form.querySelector('[data-move-in-terms-confirmation]');

        const parseDate = (value) => value ? new Date(`${value}T00:00:00`) : null;
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        const addMonthsWithoutOverflow = (date, months) => {
            const result = new Date(date);
            const originalDay = result.getDate();
            result.setDate(1);
            result.setMonth(result.getMonth() + months);
            const lastDay = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate();
            result.setDate(Math.min(originalDay, lastDay));
            return result;
        };
        const dayDifference = (from, to) => Math.round((to.getTime() - from.getTime()) / 86400000);
        const resetConfirmation = () => {
            if (termsConfirmed) termsConfirmed.checked = false;
        };
        const showRoomAvailability = (message, blocked = false) => {
            if (!roomAvailabilityMessage) return;
            roomAvailabilityMessage.textContent = message;
            roomAvailabilityMessage.classList.remove(
                'hidden', 'border-amber-300', 'bg-amber-50', 'text-amber-900',
                'border-rose-300', 'bg-rose-50', 'text-rose-800',
            );
            roomAvailabilityMessage.classList.add(...(blocked
                ? ['border-rose-300', 'bg-rose-50', 'text-rose-800']
                : ['border-amber-300', 'bg-amber-50', 'text-amber-900']));
        };
        const applyRoomAvailability = () => {
            const option = roomInput?.selectedOptions[0];
            const roomCode = option?.dataset.roomCode || '';
            const occupiedUntil = option?.dataset.occupiedUntil || '';
            const availableFrom = option?.dataset.availableFrom || '';
            const blocked = option?.dataset.availabilityBlocked === '1';

            if (blocked) {
                showRoomAvailability(
                    `Phòng ${roomCode} vẫn còn khách và chưa xác định được ngày trống. Hãy checkout hợp đồng hiện tại trước khi xếp lịch mới.`,
                    true,
                );
                if (startInput) {
                    startInput.value = '';
                    startInput.disabled = true;
                    startInput.removeAttribute('min');
                }
                return false;
            }

            if (startInput) {
                startInput.disabled = false;
                if (availableFrom) {
                    startInput.min = availableFrom;
                    if (!startInput.value || startInput.value < availableFrom) startInput.value = availableFrom;
                } else {
                    startInput.removeAttribute('min');
                }
            }

            if (availableFrom) {
                showRoomAvailability(
                    `Phòng ${roomCode} đang có khách đến hết ${parseDate(occupiedUntil)?.toLocaleDateString('vi-VN')}. Hợp đồng mới chỉ được bắt đầu từ ${parseDate(availableFrom)?.toLocaleDateString('vi-VN')}.`,
                );
            } else if (roomAvailabilityMessage) {
                roomAvailabilityMessage.classList.add('hidden');
                roomAvailabilityMessage.textContent = '';
            }

            return true;
        };
        const hideDeadlineError = () => {
            deadlineError?.classList.add('hidden');
            if (deadlineError) deadlineError.textContent = '';
        };
        const enforceDeadlineBounds = () => {
            if (!deadlineInput?.value) return true;
            if (deadlineInput.min && deadlineInput.value < deadlineInput.min) {
                deadlineInput.value = deadlineInput.min;
                if (deadlineError) {
                    deadlineError.textContent = 'Hạn cuối không được trước ngày dự kiến nhận phòng. Hệ thống đã đưa về ngày hợp lệ gần nhất.';
                    deadlineError.classList.remove('hidden');
                }
                return false;
            }
            if (deadlineInput.max && deadlineInput.value > deadlineInput.max) {
                deadlineInput.value = deadlineInput.max;
                if (deadlineError) {
                    deadlineError.textContent = 'Hạn cuối không được sau ngày kết thúc hợp đồng. Hệ thống đã đưa về ngày hợp lệ gần nhất.';
                    deadlineError.classList.remove('hidden');
                }
                return false;
            }
            hideDeadlineError();
            return true;
        };
        const updateWindowWarning = () => {
            const start = parseDate(startInput?.value);
            const end = parseDate(endInput?.value);
            const deadline = parseDate(deadlineInput?.value);
            const totalDays = start && end ? dayDifference(start, end) : 0;
            const moveInWindowDays = start && deadline ? dayDifference(start, deadline) : 0;
            const isUnusuallyLong = totalDays > 0 && moveInWindowDays / totalDays > 0.5;
            deadlineWarning?.classList.toggle('hidden', !isUnusuallyLong);
        };

        const refreshSchedule = (suggestDeadline = false) => {
            const roomCanBeScheduled = applyRoomAvailability();
            const start = parseDate(startInput?.value);
            const durationOption = durationInput?.value || '';
            const shortTerm = durationOption === 'short_term';
            const duration = Number(durationOption || 0);
            termsConfirmation?.classList.toggle('hidden', !shortTerm);
            if (termsConfirmed) {
                termsConfirmed.required = shortTerm;
                termsConfirmed.disabled = !shortTerm;
                if (!shortTerm) termsConfirmed.checked = false;
            }
            if (endInput) {
                endInput.readOnly = !shortTerm;
                endInput.classList.toggle('bg-slate-50', !shortTerm);
                endInput.classList.toggle('text-slate-600', !shortTerm);
            }
            if (deadlineInput) {
                deadlineInput.readOnly = !shortTerm;
                deadlineInput.setAttribute('aria-readonly', shortTerm ? 'false' : 'true');
                deadlineInput.classList.toggle('pointer-events-none', !shortTerm);
                deadlineInput.classList.toggle('bg-slate-50', !shortTerm);
                deadlineInput.classList.toggle('text-slate-600', !shortTerm);
                if (shortTerm) {
                    deadlineInput.removeAttribute('tabindex');
                } else {
                    deadlineInput.tabIndex = -1;
                }
            }
            if (!roomCanBeScheduled || !start || (!shortTerm && ![3, 6, 12].includes(duration))) {
                if (endInput) {
                    endInput.value = '';
                    endInput.removeAttribute('min');
                    endInput.removeAttribute('max');
                }
                if (moveInInput) {
                    moveInInput.disabled = true;
                    moveInInput.removeAttribute('min');
                    moveInInput.removeAttribute('max');
                }
                if (deadlineInput) {
                    deadlineInput.disabled = true;
                    deadlineInput.value = '';
                    deadlineInput.removeAttribute('min');
                    deadlineInput.removeAttribute('max');
                }
                hideDeadlineError();
                deadlineWarning?.classList.add('hidden');
                return;
            }

            const startValue = formatDate(start);
            if (endInput && shortTerm) {
                const earliestEnd = new Date(start);
                earliestEnd.setDate(earliestEnd.getDate() + 1);
                const maximumEnd = addMonthsWithoutOverflow(start, 3);
                const earliestEndValue = formatDate(earliestEnd);
                const maximumEndValue = formatDate(maximumEnd);
                endInput.min = earliestEndValue;
                endInput.max = maximumEndValue;
                if (endInput.value <= startValue || endInput.value > maximumEndValue) {
                    endInput.value = '';
                }
            } else if (endInput) {
                endInput.removeAttribute('min');
                endInput.removeAttribute('max');
                endInput.value = formatDate(addMonthsWithoutOverflow(start, duration));
            }
            const end = parseDate(endInput?.value);
            if (!end || end <= start) {
                if (moveInInput) moveInInput.disabled = true;
                if (deadlineInput) {
                    deadlineInput.disabled = true;
                    deadlineInput.value = '';
                }
                hideDeadlineError();
                deadlineWarning?.classList.add('hidden');
                return;
            }
            const endValue = formatDate(end);
            const fixedDeadline = new Date(start);
            fixedDeadline.setDate(fixedDeadline.getDate() + 10);
            if (fixedDeadline > end) fixedDeadline.setTime(end.getTime());
            const fixedDeadlineValue = formatDate(fixedDeadline);
            if (moveInInput) {
                moveInInput.disabled = false;
                moveInInput.min = startValue;
                moveInInput.max = shortTerm ? endValue : fixedDeadlineValue;
                if (!moveInInput.value || moveInInput.value < startValue || moveInInput.value > moveInInput.max) {
                    moveInInput.value = startValue;
                }
            }
            if (deadlineInput) {
                deadlineInput.disabled = false;
                if (!shortTerm) {
                    deadlineInput.min = fixedDeadlineValue;
                    deadlineInput.max = fixedDeadlineValue;
                    deadlineInput.value = fixedDeadlineValue;
                    hideDeadlineError();
                } else {
                    const earliestDeadline = moveInInput?.value || startValue;
                    deadlineInput.min = earliestDeadline;
                    deadlineInput.max = endValue;
                    const invalidDeadline = !deadlineInput.value
                        || deadlineInput.value < earliestDeadline
                        || deadlineInput.value > endValue;
                    if (suggestDeadline || invalidDeadline) {
                        const suggestedDays = Math.min(10, Math.max(1, Math.ceil(dayDifference(start, end) * 0.3)));
                        const suggestedDeadline = new Date(start);
                        suggestedDeadline.setDate(suggestedDeadline.getDate() + suggestedDays);
                        const earliest = parseDate(earliestDeadline);
                        if (earliest && suggestedDeadline < earliest) suggestedDeadline.setTime(earliest.getTime());
                        if (suggestedDeadline > end) suggestedDeadline.setTime(end.getTime());
                        deadlineInput.value = formatDate(suggestedDeadline);
                    }
                }
            }
            enforceDeadlineBounds();
            updateWindowWarning();
        };

        roomInput?.addEventListener('change', () => {
            resetConfirmation();
            refreshSchedule(true);
        });
        startInput?.addEventListener('input', () => {
            if (startInput.min && startInput.value && startInput.value < startInput.min) {
                startInput.value = startInput.min;
            }
        });
        startInput?.addEventListener('change', () => {
            resetConfirmation();
            refreshSchedule(true);
        });
        durationInput?.addEventListener('change', () => {
            resetConfirmation();
            refreshSchedule(true);
        });
        endInput?.addEventListener('change', () => {
            resetConfirmation();
            refreshSchedule(true);
        });
        moveInInput?.addEventListener('change', () => {
            resetConfirmation();
            refreshSchedule(true);
        });
        deadlineInput?.addEventListener('input', () => {
            resetConfirmation();
            enforceDeadlineBounds();
            updateWindowWarning();
        });
        deadlineInput?.addEventListener('change', () => {
            resetConfirmation();
            enforceDeadlineBounds();
            updateWindowWarning();
        });
        refreshSchedule(false);
    });

    document.querySelectorAll('[data-contract-occupants]').forEach((container) => {
        const list = container.querySelector('[data-occupant-list]');
        const template = container.querySelector('[data-occupant-template]');
        const addButton = container.querySelector('[data-add-occupant]');
        const countLabel = container.querySelector('[data-occupant-count]');
        const emptyState = container.querySelector('[data-empty-occupants]');
        const limitState = container.querySelector('[data-occupant-limit]');
        const representativeResident = container.closest('form')?.querySelector('[data-representative-resident]');
        const roomSelector = container.closest('form')?.querySelector('[name="room_id"]');
        let nextIndex = container.querySelectorAll('[data-occupant-row]').length;
        let lastValidRoomValue = roomSelector?.value || '';
        let roomCapacityError = '';

        const refresh = () => {
            const count = list?.querySelectorAll('[data-occupant-row]').length || 0;
            const maximum = Number(roomSelector?.selectedOptions[0]?.dataset.maxPeople || 0);
            if (representativeResident?.checked && maximum > 0 && count + 1 > maximum) {
                representativeResident.checked = false;
            }
            if (representativeResident) {
                representativeResident.disabled = !maximum || (!representativeResident.checked && count >= maximum);
                representativeResident.closest('label')?.classList.toggle('opacity-50', representativeResident.disabled);
                representativeResident.closest('label')?.classList.toggle('cursor-not-allowed', representativeResident.disabled);
            }
            const representativeCount = representativeResident?.checked ? 1 : 0;
            const total = count + representativeCount;
            roomSelector?.querySelectorAll('option[data-max-people]').forEach((option) => {
                const optionMaximum = Number(option.dataset.maxPeople || 0);
                option.disabled = option.value !== roomSelector.value && optionMaximum > 0 && count > optionMaximum;
            });
            if (countLabel) {
                countLabel.textContent = maximum ? `${total}/${maximum} người` : `${total} người`;
            }
            emptyState?.classList.toggle('hidden', count > 0);
            const reachedCapacity = maximum > 0 && total >= maximum;
            if (addButton) {
                addButton.disabled = !maximum || reachedCapacity;
                addButton.classList.toggle('cursor-not-allowed', addButton.disabled);
                addButton.classList.toggle('opacity-50', addButton.disabled);
            }
            if (limitState) {
                limitState.classList.toggle('hidden', !reachedCapacity && !roomCapacityError);
                limitState.textContent = roomCapacityError || (reachedCapacity
                    ? `Phòng chỉ chứa tối đa ${maximum} người. Đã đạt giới hạn của phòng.`
                    : '');
            }
        };

        const bindRow = (row) => {
            row.querySelector('[data-remove-occupant]')?.addEventListener('click', () => {
                row.remove();
                roomCapacityError = '';
                refresh();
            });
        };

        list?.querySelectorAll('[data-occupant-row]').forEach(bindRow);
        addButton?.addEventListener('click', () => {
            const maximum = Number(roomSelector?.selectedOptions[0]?.dataset.maxPeople || 0);
            const current = (list?.querySelectorAll('[data-occupant-row]').length || 0) + (representativeResident?.checked ? 1 : 0);
            if (!list || !template || !maximum || current >= maximum) {
                refresh();
                return;
            }
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
            const row = wrapper.firstElementChild;
            if (row) {
                list.appendChild(row);
                bindRow(row);
                row.querySelector('input:not([type="hidden"])')?.focus();
            }
            roomCapacityError = '';
            refresh();
        });
        representativeResident?.addEventListener('change', () => {
            roomCapacityError = '';
            refresh();
        });
        roomSelector?.addEventListener('change', () => {
            const residentCount = list?.querySelectorAll('[data-occupant-row]').length || 0;
            const selectedMaximum = Number(roomSelector.selectedOptions[0]?.dataset.maxPeople || 0);
            if (selectedMaximum > 0 && residentCount > selectedMaximum) {
                const rejectedMaximum = selectedMaximum;
                roomSelector.value = lastValidRoomValue;
                roomCapacityError = `Không thể đổi phòng: đã khai báo ${residentCount} người ở nhưng phòng vừa chọn chỉ chứa tối đa ${rejectedMaximum} người.`;
                refresh();
                return;
            }
            lastValidRoomValue = roomSelector.value;
            roomCapacityError = '';
            refresh();
        });
        refresh();
    });

    document.querySelectorAll('[data-ajax-validation-form]').forEach((form) => {
        const clearFieldError = (field) => {
            if (!field?.name) return;
            delete field.dataset.ajaxInvalid;
            field.classList.remove(
                'border-rose-500', 'ring-2', 'ring-rose-300',
                'focus:border-rose-500', 'focus:ring-rose-100',
            );
            form.querySelectorAll('[data-ajax-field-error], [data-validation-error-for]').forEach((error) => {
                if ((error.dataset.errorFor || error.dataset.validationErrorFor) === field.name) error.remove();
            });
        };
        const clearErrors = () => {
            Array.from(form.elements).forEach(clearFieldError);
            form.querySelectorAll('[data-ajax-field-error], [data-validation-error-for], [data-ajax-error-summary]')
                .forEach((element) => element.remove());
        };
        const fieldNameFromErrorKey = (key) => {
            const parts = key.split('.');
            return `${parts.shift()}${parts.map((part) => `[${part}]`).join('')}`;
        };
        const findField = (name) => Array.from(form.elements).find((field) => field.name === name);
        const fieldSignature = (field) => {
            if (field.type === 'file') {
                return Array.from(field.files || []).map((file) => `${file.name}:${file.size}:${file.lastModified}`).join('|');
            }
            if (field.type === 'checkbox' || field.type === 'radio') return field.checked ? field.value : '';
            return field.value;
        };
        const reindexOccupantRows = () => {
            form.querySelectorAll('[data-occupant-row]').forEach((row, index) => {
                row.querySelectorAll('[name^="occupants["]').forEach((field) => {
                    field.name = field.name.replace(/^occupants\[[^\]]+\]/, `occupants[${index}]`);
                });
            });
        };
        const displayErrors = (errors, submittedValues = null) => {
            const summary = document.createElement('div');
            summary.dataset.ajaxErrorSummary = 'true';
            summary.className = 'm-5 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800';
            const title = document.createElement('p');
            title.className = 'font-bold';
            title.textContent = 'Vui lòng kiểm tra lại các thông tin được đánh dấu.';
            const list = document.createElement('ul');
            list.className = 'mt-2 list-disc space-y-1 pl-5';
            summary.append(title, list);

            let firstInvalidField = null;
            let visibleErrorCount = 0;
            Object.entries(errors).forEach(([key, messages]) => {
                const normalizedMessages = Array.isArray(messages) ? messages : [messages];
                const field = findField(fieldNameFromErrorKey(key));
                if (field && submittedValues?.has(field.name)
                    && fieldSignature(field) !== submittedValues.get(field.name)) {
                    clearFieldError(field);
                    return;
                }
                normalizedMessages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                    visibleErrorCount++;
                });

                if (!field) return;
                firstInvalidField ||= field;
                field.dataset.ajaxInvalid = 'true';
                field.classList.add('border-rose-500');
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.classList.add('ring-2', 'ring-rose-300');
                }
                const inlineError = document.createElement('p');
                inlineError.dataset.ajaxFieldError = 'true';
                inlineError.dataset.errorFor = field.name;
                inlineError.className = 'mt-1 text-xs font-semibold text-rose-600';
                inlineError.textContent = normalizedMessages[0];
                field.insertAdjacentElement('afterend', inlineError);
            });

            if (!visibleErrorCount) return;
            form.prepend(summary);
            summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => firstInvalidField?.focus({ preventScroll: true }), 250);
        };

        form.addEventListener('input', (event) => clearFieldError(event.target));
        form.addEventListener('change', (event) => clearFieldError(event.target));
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors();
            reindexOccupantRows();
            const submitButtons = Array.from(form.querySelectorAll('[type="submit"]'));
            submitButtons.forEach((button) => {
                button.disabled = true;
                button.classList.add('cursor-wait', 'opacity-60');
            });

            try {
                const submittedValues = new Map(
                    Array.from(form.elements)
                        .filter((field) => field.name)
                        .map((field) => [field.name, fieldSignature(field)]),
                );
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json') ? await response.json() : {};

                if (response.ok) {
                    window.location.assign(payload.redirect || response.url || window.location.href);
                    return;
                }
                if (response.status === 422 && payload.errors) {
                    displayErrors(payload.errors, submittedValues);
                    return;
                }
                displayErrors({ form: [payload.message || 'Không thể lưu bản nháp lúc này. Vui lòng thử lại.'] });
            } catch (_) {
                displayErrors({ form: ['Không thể kết nối tới máy chủ. Dữ liệu trên form vẫn được giữ nguyên, vui lòng thử lại.'] });
            } finally {
                submitButtons.forEach((button) => {
                    button.disabled = false;
                    button.classList.remove('cursor-wait', 'opacity-60');
                });
            }
        });
    });

    [
        {
            filterSelector: '[data-tenant-filter]',
            searchSelector: '[data-tenant-search]',
            statusSelector: '[data-tenant-status]',
            resultsSelector: '[data-tenant-results]',
            errorSelector: '[data-tenant-filter-error]',
            exportSelector: '[data-tenant-export]',
            loadingSelector: '[data-tenant-loading]',
            paginationSelector: '[data-tenant-pagination]',
            searchParam: 'search',
        },
        {
            filterSelector: '[data-room-filter]',
            searchSelector: '[data-room-search]',
            statusSelector: '[data-room-status]',
            resultsSelector: '[data-room-results]',
            errorSelector: '[data-room-filter-error]',
            exportSelector: '[data-room-export]',
            loadingSelector: '[data-room-loading]',
            paginationSelector: '[data-room-pagination]',
            searchParam: 'room_code',
        },
        {
            filterSelector: '[data-contract-filter]',
            searchSelector: '[data-contract-search]',
            statusSelector: '[data-contract-status]',
            resultsSelector: '[data-contract-results]',
            errorSelector: '[data-contract-filter-error]',
            exportSelector: '[data-contract-export]',
            loadingSelector: '[data-contract-loading]',
            paginationSelector: '[data-contract-pagination]',
            searchParam: 'keyword',
        },
    ].forEach((config) => {
        document.querySelectorAll(config.filterSelector).forEach((filter) => {
            const form = filter.querySelector('form');
            const searchInput = filter.querySelector(config.searchSelector);
            const statusInput = filter.querySelector(config.statusSelector);
            const results = document.querySelector(config.resultsSelector);
            const errorMessage = document.querySelector(config.errorSelector);
            const exportLink = document.querySelector(config.exportSelector);
            if (!form || !searchInput || !statusInput || !results) return;

            let debounceTimer;
            let activeRequest;
            const filterUrl = (pageUrl = form.action) => {
                const url = new URL(pageUrl, window.location.origin);
                url.searchParams.delete(config.searchParam);
                url.searchParams.delete('status');
                if (searchInput.value.trim()) url.searchParams.set(config.searchParam, searchInput.value.trim());
                if (statusInput.value) url.searchParams.set('status', statusInput.value);
                return url;
            };
            const syncExportLink = (url) => {
                if (!exportLink) return;
                const exportUrl = new URL(exportLink.href, window.location.origin);
                exportUrl.search = url.search;
                exportLink.href = exportUrl.toString();
            };
            const setLoading = (loading) => {
                results.classList.toggle('opacity-50', loading);
                results.classList.toggle('pointer-events-none', loading);
                results.querySelector(config.loadingSelector)?.classList.toggle('hidden', !loading);
            };
            const loadResults = async (url, updateAddress = true) => {
                activeRequest?.abort();
                activeRequest = new AbortController();
                errorMessage?.classList.add('hidden');
                setLoading(true);
                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                        signal: activeRequest.signal,
                    });
                    if (!response.ok) throw new Error('Không thể tải danh sách.');
                    results.innerHTML = await response.text();
                    if (updateAddress) window.history.replaceState({}, '', url);
                    syncExportLink(url);
                } catch (error) {
                    if (error.name !== 'AbortError') errorMessage?.classList.remove('hidden');
                } finally {
                    setLoading(false);
                }
            };
            const scheduleFilter = (immediate = false) => {
                window.clearTimeout(debounceTimer);
                const run = () => loadResults(filterUrl());
                immediate ? run() : debounceTimer = window.setTimeout(run, 250);
            };

            form.addEventListener('submit', (event) => event.preventDefault());
            searchInput.addEventListener('input', () => scheduleFilter(false));
            statusInput.addEventListener('change', () => scheduleFilter(true));
            results.addEventListener('click', (event) => {
                const link = event.target.closest(`${config.paginationSelector} a`);
                if (!link) return;
                event.preventDefault();
                loadResults(filterUrl(link.href));
            });
            window.addEventListener('popstate', () => {
                const currentUrl = new URL(window.location.href);
                searchInput.value = currentUrl.searchParams.get(config.searchParam) || '';
                statusInput.value = currentUrl.searchParams.get('status') || '';
                loadResults(currentUrl, false);
            });
            syncExportLink(filterUrl(window.location.href));
        });
    });

    document.querySelectorAll('[data-inventory-selector]').forEach((container) => {
        const checkboxes = Array.from(container.querySelectorAll('[data-inventory-checkbox]'));
        const toggle = container.querySelector('[data-inventory-toggle-all]');
        const toggleLabel = toggle?.querySelector('span');
        const selectionCount = container.querySelector('[data-inventory-selection-count]');

        const updateInventoryToggle = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            const allSelected = checkboxes.length > 0 && selected === checkboxes.length;

            if (toggleLabel) {
                toggleLabel.textContent = allSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
            }
            if (selectionCount) {
                selectionCount.textContent = `${selected}/${checkboxes.length} mục đã chọn`;
            }
            toggle?.setAttribute('aria-pressed', allSelected ? 'true' : 'false');
        };

        toggle?.addEventListener('click', () => {
            const shouldSelect = checkboxes.some((checkbox) => !checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldSelect;
            });
            updateInventoryToggle();
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateInventoryToggle));
        updateInventoryToggle();
    });

    document.querySelectorAll('.js-image-preview-input').forEach((input) => {
        const preview = document.getElementById(input.dataset.previewTarget);
        const grid = preview?.querySelector('[data-preview-grid]');
        const count = preview?.querySelector('[data-preview-count]');
        const error = preview?.querySelector('[data-preview-error]');
        const maximum = Number(input.dataset.maxFiles || 15);

        if (!preview || !grid || !window.DataTransfer) {
            return;
        }

        let files = [];
        let objectUrls = [];

        const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;

        const synchronizeInput = () => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const render = () => {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls = [];
            grid.replaceChildren();
            preview.classList.toggle('hidden', files.length === 0);

            if (count) {
                count.textContent = `${files.length} ảnh đã chọn`;
            }

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                objectUrls.push(url);

                const card = document.createElement('div');
                card.className = 'group relative w-32 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm';

                const image = document.createElement('img');
                image.src = url;
                image.alt = file.name;
                image.className = 'h-24 w-full object-cover';

                const filename = document.createElement('p');
                filename.className = 'truncate px-2 py-1.5 text-xs text-slate-600';
                filename.title = file.name;
                filename.textContent = file.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-rose-600 text-lg font-bold leading-none text-white opacity-100 shadow transition hover:bg-rose-700 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-white sm:opacity-0 sm:group-hover:opacity-100';
                remove.setAttribute('aria-label', `Bỏ ảnh ${file.name}`);
                remove.title = 'Bỏ ảnh khỏi danh sách tải lên';
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    files.splice(index, 1);
                    if (error && files.length <= maximum) {
                        error.classList.add('hidden');
                    }
                    synchronizeInput();
                    render();
                });

                card.append(image, filename, remove);
                grid.append(card);
            });
        };

        input.addEventListener('change', () => {
            const knownKeys = new Set(files.map(fileKey));
            const incoming = Array.from(input.files).filter((file) => !knownKeys.has(fileKey(file)));
            files = [...files, ...incoming];

            if (files.length > maximum) {
                files = files.slice(0, maximum);
                if (error) {
                    error.textContent = `Chỉ được chọn tối đa ${maximum} ảnh.`;
                    error.classList.remove('hidden');
                }
            } else if (error) {
                error.classList.add('hidden');
            }

            synchronizeInput();
            render();
        });

        input.form?.addEventListener('reset', () => {
            files = [];
            synchronizeInput();
            render();
        });

        window.addEventListener('beforeunload', () => {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
        });
    });
});
