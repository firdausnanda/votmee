import { InfiniteScroll, Link } from '@inertiajs/react';
import { useState } from 'react';
import { dictionary, Layout, NavIcon, numberLabel, PageHead, RankingCard, path } from './Shared';

export default function Saved({ locale, categories, leaders, feed, savedCount }) {
    const t = dictionary(locale);
    const isId = locale === 'id';
    const [count, setCount] = useState(savedCount);
    const updateCount = (post, isSaved) => setCount((current) => Math.max(0, current + (isSaved ? 1 : -1)));

    return <Layout locale={locale} categories={categories} leaders={leaders} searchPosts={feed.data} active="saved">
        <PageHead
            title={isId ? 'Ranking tersimpan' : 'Saved rankings'}
            locale={locale}
            description={isId ? 'Ranking yang Anda simpan untuk dibaca dan didukung nanti.' : 'Rankings you saved to read and support later.'}
        />
        <div className="vx-section-heading vs-saved-heading">
            <div>
                <span className="vx-section-kicker">{isId ? 'KOLEKSI ANDA' : 'YOUR COLLECTION'}</span>
                <h1 className="vs-saved-title">{isId ? 'Ranking tersimpan' : 'Saved rankings'}<span>{numberLabel(count, locale)}</span></h1>
                <p>{isId ? 'Hanya Anda yang dapat melihat daftar ini.' : 'Only you can see this list.'}</p>
            </div>
        </div>

        {feed.data.length ? <InfiniteScroll
            data="feed"
            buffer={360}
            className="vt-feed-list vm-feed-list"
            loading={() => <p className="vt-feed-scroll-status" role="status">{isId ? 'Memuat ranking...' : 'Loading rankings...'}</p>}
            next={({ loading, fetch, hasMore }) => <div className="vt-feed-scroll-footer">
                {loading ? <span role="status">{isId ? 'Memuat ranking...' : 'Loading rankings...'}</span>
                    : hasMore ? <button type="button" onClick={fetch}>{t.loadMore}</button>
                        : <span role="status">{isId ? 'Semua ranking tersimpan sudah ditampilkan.' : 'You have seen all saved rankings.'}</span>}
            </div>}
        >
            {feed.data.map(post => <RankingCard key={post.id} post={post} locale={locale} category={categories.find(item => item.id === post.category_id)} onSavedChange={updateCount} />)}
        </InfiniteScroll> : <div className="vt-empty">
            <div className="vt-empty-mark"><NavIcon name="bookmark" size={22} /></div>
            <h2>{isId ? 'Belum ada ranking tersimpan' : 'No saved rankings yet'}</h2>
            <p>{isId ? 'Ketuk ikon simpan pada ranking mana pun untuk mengumpulkannya di sini.' : 'Tap the save icon on any ranking to collect it here.'}</p>
            <Link href={path(locale, '/explore')}>{t.explore} →</Link>
        </div>}
    </Layout>;
}
