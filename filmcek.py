import json
import requests
import os
from dotenv import load_dotenv

# .env dosyasındaki değişkenleri yükle
load_dotenv()

# API anahtarını sistem değişkenlerinden veya .env dosyasından çek
TMDB_API_KEY = os.getenv("TMDB_API_KEY")

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

def get_genre_by_imdb_id(imdb_id):
    """IMDb ID kullanarak TMDB'den tür bilgisini çeker."""
    if not TMDB_API_KEY or not imdb_id or not imdb_id.startswith('tt'):
        return None
    try:
        url = f"https://api.themoviedb.org/3/find/{imdb_id}?api_key={TMDB_API_KEY}&external_source=imdb_id&language=tr-TR"
        res = requests.get(url, timeout=5).json()
        results = res.get('movie_results', [])
        if results:
            genre_ids = results[0].get('genre_ids', [])
            tm_map = {
                28:"Aksiyon", 12:"Macera", 16:"Animasyon", 35:"Komedi", 80:"Suç", 
                99:"Belgesel", 18:"Dram", 10751:"Aile", 14:"Fantastik", 36:"Tarih", 
                27:"Korku", 10402:"Müzikal", 9648:"Gizem", 10749:"Romantik", 
                878:"Bilim Kurgu", 53:"Gerilim", 10752:"Savaş", 37:"Western"
            }
            for g_id in genre_ids:
                if g_id in tm_map: return tm_map[g_id].upper()
    except: pass
    return None

def fetch_and_convert():
    api_url = "https://yabancidizibox.com/api/discover?contentType=movie&limit=50000"
    headers = {'User-Agent': 'Mozilla/5.0'}
    
    try:
        print("Veri indiriliyor...")
        response = requests.get(api_url, headers=headers, timeout=120)
        data = response.json()
        
        with open('film_kod.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            
        movies = data.get('movies', [])
        processed_movies = []

        print(f"Toplam {len(movies)} film analiz ediliyor...")
        
        for movie in movies:
            origin_raw = str(movie.get('origin_type') or "yabanci").lower()
            lang_type = str(movie.get('language_type') or "")
            is_yerli = "yerli" in origin_raw

            # FİLTRE: Eğer film yerli DEĞİLSE ve Dublaj & Altyazı DEĞİLSE atla
            if not is_yerli and lang_type != "Dublaj & Altyazı":
                continue
            
            title = str(movie.get('title') or "")
            year = str(movie.get('year') or "0")
            imdb_id = str(movie.get('imdb_id') or "")
            rating = str(movie.get('imdb_rating') or "0.0")
            origin_text = "YERLi" if is_yerli else "YABANCI"
            poster = str(movie.get('poster_url') or "").replace('.avif', '.jpg')
            
            genre_name = ""
            genres = movie.get('genres') or []
            
            # 1. API'den gelen tür kodlarına bak
            if isinstance(genres, list):
                for g_id in genres:
                    if g_id in genre_map:
                        genre_name = genre_map[g_id].upper()
                        break
            
            # 2. Tür hala bulunamadıysa TMDB'den çek (GENEL başlığına düşmesin)
            if not genre_name:
                tmdb_res = get_genre_by_imdb_id(imdb_id)
                genre_name = tmdb_res if tmdb_res else "DRAM"

            group_title = f"{origin_text} {genre_name} FiLMLERi 🎬"
            
            processed_movies.append({
                "group": group_title,
                "year": year,
                "title": title,
                "rating": rating,
                "poster": poster,
                "imdb_id": imdb_id
            })

        # Kategori ve yıla göre sırala
        processed_movies.sort(key=lambda x: (x['group'], -int(x['year']) if x['year'].isdigit() else 0))

        # M3U Yazımı
        output = ["#EXTM3U"]
        for m in processed_movies:
            output.append(f'#EXTINF:-1 tvg-logo="{m["poster"]}" group-title="{m["group"]}",{m["title"]} ({m["year"]}) | IMDb ⭐{m["rating"]}')
            output.append(f'https://vidmody.com/vs/{m["imdb_id"]}')

        with open('master.m3u', 'w', encoding='utf-8') as f:
            f.write("\n".join(output))
            
        print(f"Bitti! Toplam {len(processed_movies)} film (Yerli + Dublaj & Altyazı) listelendi.")

    except Exception as e:
        print(f"Hata: {e}")

if __name__ == "__main__":
    fetch_and_convert()