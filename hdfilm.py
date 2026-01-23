import time
import re
import os
from seleniumwire import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from webdriver_manager.chrome import ChromeDriverManager

# --- AYARLAR ---
BASE_URL = "https://www.hdfilmizle.life/tur/yerli-film-izle-1/page"
START_PAGE = 1
EXTENSION_FILE = "ublock.crx" 
M3U_FILE = "yerli_master.m3u"
# ---------------

def tr_upper(metin):
    """Türkçe karakterleri düzgün şekilde büyük harfe çevirir."""
    duzeltmeler = {
        "i": "İ", "ı": "I", "ç": "Ç", "ş": "Ş", "ğ": "Ğ", "ü": "Ü", "ö": "Ö"
    }
    for k, v in duzeltmeler.items():
        metin = metin.replace(k, v)
    return metin.upper()

def get_existing_links():
    """Mevcut m3u dosyasındaki linkleri bir set olarak döndürür."""
    existing_links = set()
    if os.path.exists(M3U_FILE):
        with open(M3U_FILE, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line.startswith("http"):
                    existing_links.add(line)
    return existing_links

def get_movie_data(driver):
    try:
        # 1. Film Adı ve Yılını Çekme
        items = driver.find_elements(By.CSS_SELECTOR, '.breadcrumb-item span[itemprop="name"]')
        if len(items) >= 2:
            name = items[-1].text.strip()
            year = items[-2].text.strip()
            base_title = f"{name} ({year})" if re.match(r"^\d{4}$", year) else name
        else:
            base_title = driver.title.split('|')[0].strip()

        # 2. IMDb Puanını Çekme
        try:
            rating_element = driver.find_element(By.CSS_SELECTOR, '.watch-rating-value .rate span')
            rating_value = rating_element.text.strip()
            title = f"{base_title} | IMDb ⭐{rating_value}" if rating_value else base_title
        except:
            title = base_title

        # 3. Tür Bilgisi (TÜRKÇE BÜYÜK HARF FORMATI)
        try:
            genre_element = driver.find_element(By.CSS_SELECTOR, '.genres a')
            raw_genre = genre_element.text.strip()
            # "Yerli Dram Filmleri" formatını Türkçe karakter hassasiyetiyle büyütüyoruz
            genre_name = tr_upper(raw_genre)
            genre = f"YERLİ {genre_name} FİLMLERİ 🇹🇷🎬"
        except:
            genre = "YERLİ FİLMLER 🇹🇷🎬"

        # 4. Logo / Afiş Bilgisi
        try:
            img = driver.find_element(By.CSS_SELECTOR, 'img.poster-auto')
            logo = img.get_attribute('data-src') or img.get_attribute('src')
            if logo and logo.startswith('/'):
                logo = "https://www.hdfilmizle.life" + logo
        except:
            logo = ""

        return {"title": title, "genre": genre, "logo": logo}
    except Exception as e:
        print(f"Veri çekme hatası: {e}")
        return None

def click_video_player(driver):
    try:
        driver.execute_script("window.scrollBy(0, 400);")
        time.sleep(2)
        player = driver.find_elements(By.CSS_SELECTOR, '.play-that-video')
        if player:
            ActionChains(driver).move_to_element(player[0]).click().perform()
            return True
    except:
        pass
    return False

def save_m3u(info, url):
    exists = os.path.isfile(M3U_FILE)
    with open(M3U_FILE, "a", encoding="utf-8") as f:
        if not exists: f.write("#EXTM3U\n")
        f.write(f'#EXTINF:-1 tvg-logo="{info["logo"]}" group-title="{info["genre"]}",{info["title"]}\n{url}\n')

def scroll_to_load_all(driver):
    last_height = driver.execute_script("return document.body.scrollHeight")
    while True:
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(2)
        new_height = driver.execute_script("return document.body.scrollHeight")
        if new_height == last_height:
            break
        last_height = new_height

def run_bot():
    options = webdriver.ChromeOptions()
    options.add_experimental_option('excludeSwitches', ['enable-logging'])
    options.add_argument("--window-size=1920,1080")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    # options.add_argument("--headless=new")  # ❌ BUNU KAPATTIK


    if os.path.exists(EXTENSION_FILE):
        options.add_extension(os.path.abspath(EXTENSION_FILE))

    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()),
                            seleniumwire_options={'disable_encoding': True}, options=options)

    saved_links = get_existing_links()
    print(f"Sistemde kayıtlı {len(saved_links)} link var.")

    page_num = START_PAGE 
    try:
        while True:
            current_page_url = f"{BASE_URL.rstrip('/')}/{page_num}/"
            print(f"\n>>> SAYFA {page_num} TARANIYOR <<<")
            
            driver.get(current_page_url)
            time.sleep(5)
            scroll_to_load_all(driver)

            movie_links = []
            posters = driver.find_elements(By.CSS_SELECTOR, 'img.lazyloaded')
            for img in posters:
                try:
                    link_obj = driver.execute_script("return arguments[0].closest('a');", img)
                    if link_obj:
                        href = link_obj.get_attribute('href')
                        if href and "/page/" not in href and href not in movie_links:
                            if href.startswith("https://www.hdfilmizle.life/"):
                                movie_links.append(href)
                except: continue

            if not movie_links:
                break

            for link in movie_links:
                try:
                    del driver.requests
                    driver.get(link)
                    time.sleep(4)

                    # XB Kontrolü
                    try:
                        active_source = driver.find_element(By.CSS_SELECTOR, 'button.kaynak-btn.active')
                        if active_source.text.strip() == "XB":
                            continue
                    except: pass

                    info = get_movie_data(driver)
                    if not info: continue

                    click_video_player(driver)

                    found = False
                    start = time.time()
                    while time.time() - start < 35:
                        for request in driver.requests:
                            if request.response:
                                is_standard = "master.m3u8" in request.url
                                is_mx_source = "/mx/" in request.url
                                
                                if is_standard or is_mx_source:
                                    target_url = request.url.split('?')[0]
                                    if target_url in saved_links:
                                        found = True
                                        break
                                    
                                    save_m3u(info, target_url)
                                    saved_links.add(target_url) 
                                    print(f"KAYDEDİLDİ: {info['title']}")
                                    found = True
                                    break
                        if found: break
                        time.sleep(1)
                except: continue
            page_num += 1
    finally:
        driver.quit()

if __name__ == "__main__":
    run_bot()