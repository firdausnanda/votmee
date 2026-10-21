import { useEffect, useRef, useState } from "react";

function LanguageIcon({ name, size }) {
    const paths = {
        globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c3 2.5 4.5 5.5 4.5 9S15 18.5 12 21M12 3C9 5.5 7.5 8.5 7.5 12S9 18.5 12 21" /></>,
        chevronDown: <path d="m5 9 7 7 7-7" />,
        check: <path d="m5 12 4 4L19 6" />,
    };

    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}

export default function LanguageSelect({ language, onChange, mobile = false, availableLanguages = ['id', 'en'] }) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        if (!open) return;

        const closeOutside = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) setOpen(false);
        };
        const closeOnEscape = (event) => {
            if (event.key === "Escape") setOpen(false);
        };

        document.addEventListener("mousedown", closeOutside);
        document.addEventListener("touchstart", closeOutside);
        document.addEventListener("keydown", closeOnEscape);
        return () => {
            document.removeEventListener("mousedown", closeOutside);
            document.removeEventListener("touchstart", closeOutside);
            document.removeEventListener("keydown", closeOnEscape);
        };
    }, [open]);

    const options = [
        {
            code: "id", label: "Bahasa Indonesia", short: "ID",
            flag: <svg className="vm-flag-id" viewBox="0 0 20 14" width="18" height="13" aria-hidden="true"><path fill="#fff" d="M0 0h20v14H0z" /><path fill="#ed1c24" d="M0 0h20v7H0z" /></svg>,
        },
        { code: "en", label: "English", short: "EN", flag: <LanguageIcon name="globe" size={16} /> },
    ];
    const current = options.find((option) => option.code === language) || options[0];

    return <div ref={containerRef} className={`vm-lang-select-box${mobile ? " vm-mobile-language" : ""}`}>
        <button type="button" className={`vm-lang-trigger ${open ? "open" : ""}`} onClick={() => setOpen(!open)} disabled={availableLanguages.length === 1} title={availableLanguages.length === 1 ? (language === 'id' ? 'Konten ini belum tersedia dalam bahasa Inggris' : 'This content is not available in Indonesian yet') : undefined} aria-haspopup="listbox" aria-expanded={open} aria-label="Pilih bahasa / Select language">
            <span className="vm-lang-flag">{current.flag}</span>
            <span className="vm-lang-code">{current.short}</span>
            <span className={`vm-select-chevron ${open ? "rotate" : ""}`}><LanguageIcon name="chevronDown" size={13} /></span>
        </button>
        {open && <div className="vm-lang-dropdown" role="listbox" aria-label={language === "id" ? "Pilih Bahasa" : "Language"}>
            <div className="vm-select-dropdown-header"><span>{language === "id" ? "Pilih Bahasa" : "Language"}</span></div>
            {options.filter(option => availableLanguages.includes(option.code)).map((option) => {
                const selected = language === option.code;
                return <button key={option.code} type="button" role="option" aria-selected={selected} className={`vm-lang-option ${selected ? "selected" : ""}`} onClick={() => { onChange(option.code); setOpen(false); }}>
                    <span className="vm-lang-opt-main"><span className="vm-lang-opt-flag">{option.flag}</span><span className="vm-lang-opt-label">{option.label}</span></span>
                    <span className="vm-lang-opt-extra"><span className="vm-lang-opt-code">{option.short}</span>{selected && <span className="vm-select-option-check"><LanguageIcon name="check" size={14} /></span>}</span>
                </button>;
            })}
        </div>}
    </div>;
}
