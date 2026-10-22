import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const groups = {
    replies: {
        id: ['Balasan & komentar', 'Seseorang membalas komentar Anda atau mengomentari ranking Anda.'],
        en: ['Replies & comments', 'Someone replies to your comment or comments on your ranking.'],
    },
    likes: {
        id: ['Suka', 'Seseorang menyukai komentar Anda.'],
        en: ['Likes', 'Someone likes your comment.'],
    },
    messages: {
        id: ['Permintaan pesan', 'Seseorang mengirim pesan pribadi pertama kepada Anda.'],
        en: ['Message requests', 'Someone sends you a first private message.'],
    },
    badges: {
        id: ['Lencana & status', 'Lencana baru terbuka atau Anda menjadi kontributor tepercaya.'],
        en: ['Badges & status', 'You unlock a badge or become a trusted contributor.'],
    },
    reviews: {
        id: ['Hasil review', 'Kontribusi, objek, atau laporan Anda sudah ditinjau moderator.'],
        en: ['Review results', 'A moderator reviewed your contribution, object, or report.'],
    },
    streak: {
        id: ['Pengingat streak', 'Pengingat saat streak harian Anda hampir putus.'],
        en: ['Streak reminders', 'A reminder when your daily streak is about to break.'],
    },
};

export default function UpdateNotificationPreferencesForm({ locale = 'id', preferences = {} }) {
    const isId = locale === 'id';
    const [values, setValues] = useState(preferences);
    const [saved, setSaved] = useState(false);

    useEffect(() => setValues(preferences), [preferences]);
    useEffect(() => {
        if (!saved) return undefined;
        const timer = setTimeout(() => setSaved(false), 2000);

        return () => clearTimeout(timer);
    }, [saved]);

    const toggle = (group) => {
        const previous = values;
        const next = { ...values, [group]: !values[group] };
        setValues(next);
        router.patch(route('profile.notifications.update'), { preferences: next }, {
            preserveScroll: true,
            onSuccess: () => setSaved(true),
            onError: () => setValues(previous),
        });
    };

    return (
        <section className="vst-card">
            <header className="vst-card-header">
                <div className="vst-card-heading">
                    <h2 className="vst-card-title">{isId ? 'Preferensi Notifikasi' : 'Notification Preferences'}</h2>
                    <p className="vst-card-desc">
                        {isId
                            ? 'Pilih notifikasi yang ingin muncul di lonceng Anda. Perubahan tersimpan otomatis.'
                            : 'Choose which notifications appear in your bell. Changes save automatically.'}
                    </p>
                </div>
            </header>

            <ul className="vst-switch-list">
                {Object.keys(groups).map((group) => {
                    const [label, description] = groups[group][isId ? 'id' : 'en'];

                    return (
                        <li key={group} className="vst-switch-row">
                            <div className="vst-switch-copy">
                                <strong id={`pref-${group}-label`}>{label}</strong>
                                <span>{description}</span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={values[group] !== false}
                                aria-labelledby={`pref-${group}-label`}
                                className={'vst-switch' + (values[group] !== false ? ' is-on' : '')}
                                onClick={() => toggle(group)}
                            >
                                <span className="vst-switch-thumb" />
                            </button>
                        </li>
                    );
                })}
            </ul>
            <p className="vst-switch-status" role="status">{saved ? (isId ? 'Tersimpan' : 'Saved') : ''}</p>
        </section>
    );
}
