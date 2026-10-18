import { useEffect, useMemo, useRef, useState } from 'react';

/**
 * Professional SelectBox Component with Custom Animated Dropdown
 *
 * @param {Object} props
 * @param {string|number} props.value - Selected value
 * @param {Function} props.onChange - Selection change callback
 * @param {Array<Object|string>} props.options - List of options: [{ value, label, icon, iconClass, description, badge, isDivider }]
 * @param {string} [props.placeholder='Pilih opsi...'] - Trigger placeholder text
 * @param {string} [props.headerTitle] - Dropdown header title
 * @param {React.ReactNode} [props.icon] - Leading icon for the trigger button
 * @param {boolean} [props.clearable=false] - Whether to show a reset/clear button
 * @param {Function} [props.onClear] - Clear event callback (defaults to onChange(''))
 * @param {boolean} [props.searchable=false] - Whether to show search filter inside dropdown
 * @param {string} [props.searchPlaceholder='Cari...'] - Search input placeholder
 * @param {boolean} [props.showBadgeInTrigger=false] - Whether to display option badge in trigger
 * @param {string} [props.className=''] - Additional CSS class for the wrapper
 * @param {string} [props.triggerClassName=''] - Additional CSS class for the trigger button
 * @param {string} [props.dropdownClassName=''] - Additional CSS class for the dropdown menu
 * @param {'left'|'right'} [props.align='right'] - Alignment of the dropdown menu
 * @param {boolean} [props.disabled=false] - Disabled state
 * @param {string} [props.id] - Element ID
 * @param {string} [props.name] - Form field name (renders a hidden input)
 * @param {string} [props.label] - Label above select-box
 * @param {string} [props.error] - Validation error message
 * @param {string} [props.footerText] - Footer text inside dropdown
 */
export default function SelectBox({
    value,
    onChange,
    options = [],
    placeholder = 'Pilih opsi...',
    headerTitle,
    icon,
    clearable = false,
    onClear,
    searchable = false,
    searchPlaceholder = 'Cari...',
    showBadgeInTrigger = false,
    className = '',
    triggerClassName = '',
    dropdownClassName = '',
    align = 'right',
    disabled = false,
    id,
    name,
    label,
    error,
    footerText,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const containerRef = useRef(null);
    const triggerRef = useRef(null);
    const searchInputRef = useRef(null);

    // Normalize options into consistent object structure
    const normalizedOptions = useMemo(() => {
        return options.map((opt) => {
            if (typeof opt === 'object' && opt !== null) {
                return {
                    value: opt.value,
                    label: opt.label ?? String(opt.value),
                    icon: opt.icon,
                    iconClass: opt.iconClass,
                    description: opt.description,
                    badge: opt.badge,
                    isDivider: opt.isDivider || false,
                };
            }
            return {
                value: opt,
                label: String(opt),
            };
        });
    }, [options]);

    const selectedOption = useMemo(() => {
        return normalizedOptions.find(
            (opt) => opt.value === value && !opt.isDivider
        );
    }, [normalizedOptions, value]);

    // Filter options based on search query
    const displayedOptions = useMemo(() => {
        if (!searchQuery.trim()) {
            return normalizedOptions;
        }
        const query = searchQuery.trim().toLowerCase();
        return normalizedOptions.filter((opt) => {
            if (opt.isDivider) return false;
            return (
                opt.label.toLowerCase().includes(query) ||
                (opt.badge && opt.badge.toLowerCase().includes(query)) ||
                (opt.description && opt.description.toLowerCase().includes(query))
            );
        });
    }, [normalizedOptions, searchQuery]);

    // Focus search input when dropdown opens
    useEffect(() => {
        if (isOpen && searchable) {
            setTimeout(() => {
                searchInputRef.current?.focus();
            }, 60);
        } else if (!isOpen) {
            setSearchQuery('');
        }
    }, [isOpen, searchable]);

    // Close on click outside and escape key
    useEffect(() => {
        function handleClickOutside(event) {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }

        function handleKeyDown(event) {
            if (event.key === 'Escape' && isOpen) {
                setIsOpen(false);
                triggerRef.current?.focus();
            }
        }

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            document.addEventListener('touchstart', handleClickOutside);
            document.addEventListener('keydown', handleKeyDown);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('touchstart', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    const handleSelect = (val) => {
        if (onChange) {
            onChange(val);
        }
        setIsOpen(false);
        triggerRef.current?.focus();
    };

    const handleClear = (e) => {
        e.stopPropagation();
        if (onClear) {
            onClear();
        } else if (onChange) {
            onChange('');
        }
    };

    const hasFilter = selectedOption && selectedOption.value !== '' && selectedOption.value !== 'Semua Kategori';

    return (
        <div ref={containerRef} className={`vm-select-box-wrapper ${className}`}>
            {label && (
                <label htmlFor={id} className="vm-select-box-label">
                    {label}
                </label>
            )}

            {name && <input type="hidden" name={name} value={value ?? ''} />}

            <div className="vm-select-box-container">
                <button
                    ref={triggerRef}
                    type="button"
                    id={id}
                    disabled={disabled}
                    className={`vm-select-box-trigger ${isOpen ? 'is-open' : ''} ${hasFilter ? 'has-filter' : ''} ${disabled ? 'is-disabled' : ''} ${triggerClassName}`}
                    onClick={() => !disabled && setIsOpen(!isOpen)}
                    aria-haspopup="listbox"
                    aria-expanded={isOpen}
                >
                    <span className="vm-select-trigger-content">
                        {selectedOption?.icon ? (
                            <span className={`vm-select-trigger-icon ${selectedOption.iconClass || ''}`}>
                                {selectedOption.icon}
                            </span>
                        ) : icon ? (
                            <span className="vm-select-trigger-icon">{icon}</span>
                        ) : null}

                        <span className="vm-select-trigger-text">
                            {selectedOption ? selectedOption.label : placeholder}
                        </span>

                        {showBadgeInTrigger && selectedOption?.badge && (
                            <span className="vm-select-option-badge">
                                {selectedOption.badge}
                            </span>
                        )}
                    </span>

                    <span className="vm-select-trigger-actions">
                        {clearable && hasFilter && (
                            <span
                                role="button"
                                tabIndex={0}
                                className="vm-select-clear-btn"
                                onClick={handleClear}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' || e.key === ' ') {
                                        e.preventDefault();
                                        handleClear(e);
                                    }
                                }}
                                aria-label="Reset filter"
                                title="Reset filter"
                            >
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </span>
                        )}

                        <span className={`vm-select-chevron ${isOpen ? 'rotate' : ''}`}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </span>
                </button>

                {isOpen && (
                    <div
                        role="listbox"
                        className={`vm-select-dropdown ${align === 'left' ? 'align-left' : 'align-right'} ${dropdownClassName}`}
                    >
                        {headerTitle && (
                            <div className="vm-select-dropdown-header">
                                <span>{headerTitle}</span>
                                {clearable && hasFilter && (
                                    <button
                                        type="button"
                                        className="vm-select-reset-link"
                                        onClick={handleClear}
                                    >
                                        Reset
                                    </button>
                                )}
                            </div>
                        )}

                        {searchable && (
                            <div className="vm-select-search-box">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input
                                    ref={searchInputRef}
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            if (displayedOptions.length > 0) {
                                                handleSelect(displayedOptions[0].value);
                                            }
                                        }
                                    }}
                                    placeholder={searchPlaceholder}
                                    onClick={(e) => e.stopPropagation()}
                                />
                                {searchQuery && (
                                    <button
                                        type="button"
                                        className="vm-select-search-clear"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setSearchQuery('');
                                            searchInputRef.current?.focus();
                                        }}
                                        aria-label="Hapus pencarian"
                                    >
                                        ✕
                                    </button>
                                )}
                            </div>
                        )}

                        <div className="vm-select-options-list">
                            {displayedOptions.length === 0 ? (
                                <div className="vm-select-empty-state">
                                    Tidak ada hasil
                                </div>
                            ) : (
                                displayedOptions.map((opt, idx) => {
                                    if (opt.isDivider) {
                                        return <div key={`divider-${idx}`} className="vm-select-divider" />;
                                    }

                                    const isSelected = opt.value === value;

                                    return (
                                        <button
                                            key={String(opt.value)}
                                            type="button"
                                            role="option"
                                            aria-selected={isSelected}
                                            className={`vm-select-option-item ${isSelected ? 'is-selected' : ''}`}
                                            onClick={() => handleSelect(opt.value)}
                                        >
                                            <div className="vm-select-option-main">
                                                {opt.icon && (
                                                    <span className={`vm-select-option-icon ${opt.iconClass || ''}`}>
                                                        {opt.icon}
                                                    </span>
                                                )}
                                                <div className="vm-select-option-text">
                                                    <span className="vm-select-option-label">
                                                        {opt.label}
                                                    </span>
                                                    {opt.description && (
                                                        <span className="vm-select-option-desc">
                                                            {opt.description}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="vm-select-option-extra">
                                                {opt.badge && (
                                                    <span className="vm-select-option-badge">
                                                        {opt.badge}
                                                    </span>
                                                )}
                                                {isSelected && (
                                                    <span className="vm-select-option-check">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                    </span>
                                                )}
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>

                        {footerText && (
                            <div className="vm-select-dropdown-footer">
                                <span>{footerText}</span>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {error && <p className="vm-select-box-error">{error}</p>}
        </div>
    );
}
