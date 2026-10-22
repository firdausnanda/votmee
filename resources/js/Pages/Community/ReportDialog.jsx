import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useId, useState } from 'react';

export default function ReportDialog({ locale, targetType, onClose, onSubmit }) {
    const isId = locale === 'id';
    const detailsId = useId();
    const [selectedReason, setSelectedReason] = useState('');
    const [details, setDetails] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const targetLabel = {
        ranking_post: 'ranking',
        comment: isId ? 'komentar' : 'comment',
        object: isId ? 'objek' : 'object',
        object_article: isId ? 'artikel' : 'article',
        message: isId ? 'pesan' : 'message',
    }[targetType];
    const reasons = [
        isId ? 'Informasi menyesatkan' : 'Misleading information',
        isId ? 'Spam atau promosi' : 'Spam or promotion',
        isId ? 'Pelecehan atau ujaran kebencian' : 'Harassment or hate speech',
        ...(['comment', 'message'].includes(targetType) ? [] : [isId ? 'Duplikat atau kategori salah' : 'Duplicate or wrong category']),
        isId ? 'Alasan lainnya' : 'Another reason',
    ];
    const needsDetails = selectedReason === reasons[reasons.length - 1];

    const submit = async (event) => {
        event.preventDefault();

        if (!selectedReason || (needsDetails && details.trim().length < 10) || submitting) {
            return;
        }

        setError('');
        setSubmitting(true);

        try {
            await onSubmit([selectedReason, details.trim()].filter(Boolean).join(': '));
        } catch (submissionError) {
            setError(submissionError.message);
            setSubmitting(false);
        }
    };

    return (
        <Dialog open onClose={() => { if (!submitting) onClose(); }} className="vt-report-overlay">
            <div className="vt-report-backdrop" aria-hidden="true" />
            <div className="vt-report-positioner">
                <DialogPanel className="vt-report-dialog">
                    <form onSubmit={submit}>
                        <div className="vt-report-header">
                            <div>
                                <DialogTitle className="vt-report-title">
                                    {isId ? `Laporkan ${targetLabel}` : `Report ${targetLabel}`}
                                </DialogTitle>
                                <p className="vt-report-intro">
                                    {isId
                                        ? 'Bantu kami meninjau konten yang tidak sesuai dengan pedoman komunitas.'
                                        : 'Help us review content that may violate community guidelines.'}
                                </p>
                            </div>
                            <button type="button" className="vt-report-close" onClick={onClose} disabled={submitting} aria-label={isId ? 'Tutup' : 'Close'}>
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>

                        <div className="vt-report-body">
                            <fieldset className="vt-report-fieldset">
                                <legend>{isId ? 'Alasan laporan' : 'Reason for reporting'} <span aria-hidden="true">*</span></legend>
                                <p className="vt-report-hint">{isId ? 'Pilih alasan yang paling sesuai.' : 'Choose the most relevant reason.'}</p>
                                <div className="vt-report-reasons">
                                    {reasons.map((reason) => (
                                        <label key={reason} className={`vt-report-reason ${selectedReason === reason ? 'is-selected' : ''}`}>
                                            <input type="radio" name="report-reason" value={reason} checked={selectedReason === reason} onChange={() => { setSelectedReason(reason); setError(''); }} required />
                                            <span>{reason}</span>
                                        </label>
                                    ))}
                                </div>
                            </fieldset>

                            <div className="vt-report-details">
                                <label htmlFor={detailsId}>
                                    {isId ? 'Detail tambahan' : 'Additional details'}
                                    <span>{needsDetails ? (isId ? 'Wajib diisi' : 'Required') : (isId ? 'Opsional' : 'Optional')}</span>
                                </label>
                                <textarea
                                    id={detailsId}
                                    value={details}
                                    onChange={(event) => { setDetails(event.target.value); setError(''); }}
                                    minLength={needsDetails ? 10 : undefined}
                                    maxLength={1800}
                                    rows={3}
                                    required={needsDetails}
                                    placeholder={isId ? 'Ceritakan bagian yang perlu kami periksa...' : 'Tell us what we should review...'}
                                />
                                <p>{isId ? 'Sertakan konteks yang membantu tim moderasi.' : 'Include context that helps the moderation team.'}</p>
                            </div>

                            {error && <p className="vt-report-error" role="alert">{error}</p>}
                        </div>

                        <div className="vt-report-footer">
                            <p>{isId ? 'Laporan akan ditinjau oleh tim moderasi.' : 'The moderation team will review your report.'}</p>
                            <div className="vt-report-actions">
                                <button type="button" className="vt-report-cancel" onClick={onClose} disabled={submitting}>{isId ? 'Batal' : 'Cancel'}</button>
                                <button type="submit" className="vt-report-submit" disabled={!selectedReason || (needsDetails && details.trim().length < 10) || submitting}>
                                    {submitting ? (isId ? 'Mengirim...' : 'Sending...') : (isId ? 'Kirim laporan' : 'Submit report')}
                                </button>
                            </div>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    );
}
