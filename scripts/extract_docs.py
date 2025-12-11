import os
import sys
from pathlib import Path

DOCS_DIR = Path(r"c:\Users\Dalton\Downloads\KindHeart-1.0.0\docs")
OUT_DIR = DOCS_DIR / "extracted"
OUT_DIR.mkdir(parents=True, exist_ok=True)

def extract_docx(path: Path):
    try:
        from docx import Document
    except Exception as e:
        print(f"Missing python-docx: {e}")
        return
    doc = Document(path)
    texts = []
    for para in doc.paragraphs:
        texts.append(para.text)
    out_txt = OUT_DIR / (path.stem + ".txt")
    out_txt.write_text("\n".join(texts), encoding="utf-8")
    # extract images
    media_dir = OUT_DIR / (path.stem + "_images")
    media_dir.mkdir(exist_ok=True)
    try:
        import zipfile
        with zipfile.ZipFile(path, 'r') as z:
            for name in z.namelist():
                if name.startswith('word/media/'):
                    data = z.read(name)
                    out_name = media_dir / Path(name).name
                    out_name.write_bytes(data)
    except Exception as e:
        print(f"Failed to extract images from {path.name}: {e}")

def extract_pdf(path: Path):
    try:
        import fitz  # PyMuPDF
    except Exception as e:
        print(f"Missing PyMuPDF: {e}")
        return
    doc = fitz.open(path.as_posix())
    texts = []
    images_dir = OUT_DIR / (path.stem + "_images")
    images_dir.mkdir(exist_ok=True)
    img_count = 0
    for page_num in range(doc.page_count):
        page = doc.load_page(page_num)
        text = page.get_text()
        texts.append(text)
        image_list = page.get_images(full=True)
        for img_index, img in enumerate(image_list, start=1):
            xref = img[0]
            base_image = doc.extract_image(xref)
            img_bytes = base_image.get("image")
            img_ext = base_image.get("ext", "png")
            img_count += 1
            out_path = images_dir / f"image_{page_num+1}_{img_index}.{img_ext}"
            out_path.write_bytes(img_bytes)
    out_txt = OUT_DIR / (path.stem + ".txt")
    out_txt.write_text("\n".join(texts), encoding="utf-8")

def main():
    if not DOCS_DIR.exists():
        print(f"Docs dir not found: {DOCS_DIR}")
        sys.exit(1)
    files = list(DOCS_DIR.iterdir())
    for f in files:
        if f.suffix.lower() in ('.docx',):
            print(f"Extracting DOCX: {f.name}")
            extract_docx(f)
        elif f.suffix.lower() in ('.pdf',):
            print(f"Extracting PDF: {f.name}")
            extract_pdf(f)
        else:
            print(f"Skipping unsupported file: {f.name}")

    print(f"Extraction complete. Output in: {OUT_DIR}")

if __name__ == '__main__':
    main()
