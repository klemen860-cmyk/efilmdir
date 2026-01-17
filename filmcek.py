import json
import requests
import os

TMDB_API_KEY = "98c315f0bc15f70579cf309bb0f83d59"

# 1. Tür Listesi
genre_map = {
    "68a4278ee0a6ba718de9f515": "Western", "68a42728e0a6ba718de9f0f9": "Tarih",
    "68a427a1e0a6ba718de9f5f0": "Gençlik", "68a87b7867e20e9a90a3debc": "Aile",
    "68a87b5867e20e9a90a3ddfa": "Aksiyon", "68a87b6567e20e9a90a3de4b": "Animasyon",
    "68a87b9b67e20e9a90a3e1d9": "Belgesel", "68a4271be0a6ba718de9f034": "Bilim Kurgu",
    "68a4273ee0a6ba718de9f1bb": "Biyografi", "68a87b5867e20e9a90a3ddfd": "Dram",
    "68a87b7767e20e9a90a3deb9": "Fantastik", "68a87b5867e20e9a90a3ddff": "Gerilim",
    "68a87b5867e20e9a90a3ddfe": "Gizem", "68a87b6467e20e9a90a3de46": "Komedi",
    "68a87b5a67e20e9a90a3de01": "Korku", "68a87b5867e20e9a90a3ddfb": "Macera",
    "68a42751e0a6ba718de9f286": "Müzikal", "68a42736e0a6ba718de9f163": "Müzikal",
    "68e1481cc81483d3035d53fc": "Politik", "68a87b6467e20e9a90a3de47": "Romantik",
    "68a42763e0a6ba718de9f33b": "Savaş", "68a4276be0a6ba718de9f395": "Spor",
    "68a4271de0a6ba718de9f044": "Suç"
}

def get_genre_from_tmdb(title, year):
    try:
        url = f"https://api.themoviedb.org/3/search/movie?api_key={TMDB_API_KEY}&query={title}&year={year}&language=tr-TR"
        res = requests.get(url, timeout=5).json()
        if res.get('results'):
            genre_ids = res['results'][0].get('genre_ids', [])
            tm_map = {28:"Aksiyon", 12:"Macera", 16:"Animasyon", 35:"Komedi", 80:"Suç", 99:"Belgesel", 18:"Dram", 10751:"Aile", 14:"Fantastik", 36:"Tarih", 27:"Korku", 10402:"Müzikal", 9648:"Gizem", 10749:"Romantik", 878:"Bilim Kurgu", 53:"Gerilim", 10752:"Savaş", 37:"Western"}
            for g_id in genre_ids:
                if g_id in tm_map: return tm_map[g_id].upper()
    except: pass
    return None

def fetch_and_convert():
    api_url = "https://yabancidizibox.com/api/discover?contentType=movie&limit=50000"
    headers = {'User-Agent': 'Mozilla/5.0'}
    
    try:
        print("Veri indiriliyor ve 'film_kod.json' kaydediliyor...")
        response = requests.get(api_url, headers=headers, timeout=120)
        data = response.json()
        
        with open('film_kod.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            
        movies = data.get('movies', [])
        processed_movies = []

        print(f"Tarama başladı: {len(movies)} film işleniyor...")
        for movie in movies:
            title = str(movie.get('title') or "")
            year = str(movie.get('year') or "0")
            imdb_id = str(movie.get('imdb_id') or "")
            rating = str(movie.get('imdb_rating') or "0.0")
            origin_raw = str(movie.get('origin_type') or "yabanci").lower()
            
            is_yerli = "yerli" in origin_raw
            origin_text = "YERLi" if is_yerli else "YABANCI"
            poster = str(movie.get('poster_url') or "").replace('.avif', '.jpg')
            
            genre_name = ""
            genres = movie.get('genres') or []
            
            if isinstance(genres, list):
                for g_id in genres:
                    if g_id in genre_map:
                        genre_name = genre_map[g_id].upper()
                        break
            
            if not genre_name:
                tmdb_res = get_genre_from_tmdb(title, year)
                genre_name = tmdb_res if tmdb_res else "GENEL"

            group_title = f"{origin_text} {genre_name} FiLMLERi 🎬"
            
            # Verileri sıralama için bir sözlükte topla
            processed_movies.append({
                "group": group_title,
                "year": year,
                "title": title,
                "rating": rating,
                "poster": poster,
                "imdb_id": imdb_id
            })

        # --- SIRALAMA MANTIĞI ---
        # Önce Kategoriye (group) göre alfabetik, 
        # Sonra Yıla (year) göre büyükten küçüğe (reverse=True)
        processed_movies.sort(key=lambda x: (x['group'], -int(x['year']) if x['year'].isdigit() else 0))

        # M3U formatına dönüştür
        output = ["#EXTM3U"]
        for m in processed_movies:
            output.append(f'#EXTINF:-1 tvg-logo="{m["poster"]}" group-title="{m["group"]}",{m["title"]} ({m["year"]}) | IMDb ⭐{m["rating"]}')
            output.append(f'https://vidmody.com/vs/{m["imdb_id"]}')

        with open('master.m3u', 'w', encoding='utf-8') as f:
            f.write("\n".join(output))
            
        print("İşlem Başarılı! 'film_kod.json' ve 'master.m3u' dosyaları oluşturuldu.")

    except Exception as e:
        print(f"Hata: {e}")

if __name__ == "__main__":
    fetch_and_convert()