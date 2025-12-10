from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle
from reportlab.lib.units import inch

# Create the PDF file
filename = "Sales_Contract_Haozhan_Fusheng.pdf"
doc = SimpleDocTemplate(filename, pagesize=A4, rightMargin=40, leftMargin=40, topMargin=40, bottomMargin=40)

elements = []
styles = getSampleStyleSheet()

# Custom Styles
title_style = ParagraphStyle(
    'Title',
    parent=styles['Heading1'],
    alignment=1, # Center
    fontSize=16,
    spaceAfter=20
)

header_style = ParagraphStyle(
    'HeaderLabel',
    parent=styles['Normal'],
    fontSize=10,
    fontName='Helvetica-Bold',
)

normal_style = ParagraphStyle(
    'NormalText',
    parent=styles['Normal'],
    fontSize=10,
    leading=14
)

# 1. Title
elements.append(Paragraph("SALES CONTRACT", title_style))

# 2. General Information (Seller, Buyer, Dates)
info_data = [
    [Paragraph("<b>ORDER NUMBER:</b> HZ-F5-575-2025-10-08", normal_style), Paragraph("<b>DATE:</b> 2025-10-05", normal_style)],
]
t_info = Table(info_data, colWidths=[3.5*inch, 3*inch])
t_info.setStyle(TableStyle([('VALIGN', (0,0), (-1,-1), 'TOP')]))
elements.append(t_info)
elements.append(Spacer(1, 12))

# Seller & Buyer Info
seller_buyer_data = [
    [Paragraph("<b>THE SELLER:</b>", header_style), Paragraph("<b>THE BUYER:</b>", header_style)],
    [Paragraph("QUANZHOU HAOZHAN IMPORT & EXPORT TRADE CO., LTD", normal_style), 
     Paragraph("PT FUSHENG WEAVING INTERNATIONAL", normal_style)],
    [Paragraph("<b>Address:</b><br/>NO 34, BUILDING 12, MEIYU COURT, WANGCUO COMMUNITY, XIYUAN STREET, JINJIANG CITY, QUANZHOU CITY, FUJIAN PROVINCE, CHINA", normal_style),
     Paragraph("<b>Address:</b><br/>KAWASAN INDUSTRI JATENGLAND INDUSTRIAL PARK SAYUNG (JIPS) BLOK U3, Desa/Kelurahan Batu, Kec. Karangtengah, Kab. Demak, Provinsi Jawa Tengah, INDONESIA", normal_style)]
]

t_sb = Table(seller_buyer_data, colWidths=[3.5*inch, 3.5*inch])
t_sb.setStyle(TableStyle([
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ('LEFTPADDING', (0,0), (-1,-1), 0),
    ('BOTTOMPADDING', (0,0), (-1,-1), 6),
]))
elements.append(t_sb)
elements.append(Spacer(1, 12))

elements.append(Paragraph("The seller agrees to sell and the buyer agrees to buy the under mentioned goods on the terms and conditions stated below:", normal_style))
elements.append(Spacer(1, 12))

# 3. Product Table
# Data structure: Description, Specs, Qty, Unit Price, Total
table_data = [
    ["Description", "Specifications", "Quantity", "Unit Price\n(USD)", "Total Price\n(USD)"], # Header
    ["1/3 of one set prefabrication\nbuilding", "Steel structure", "413 ton", "917.34", "378,864"],
    ["", "PVC drain pipe", "243 pcs", "25", "6,075"],
    ["", "Metal color-coated\nsteel sheet", "30.5 ton", "1,410", "43,005"],
    ["1/3 of one set prefabrication\nbuilding", "Z-section steel", "46.36 ton", "700", "35,697.2"],
    ["", "C-section steel", "36.44 ton", "700", "28,058.8"],
    ["1/3 of one set prefabrication\nbuilding", "Metal Cold Roll\nForming Machine", "1 set", "22,300", "22,300"],
    ["Shipment", "TO SEMARANG, INDONESIA BY SEA", "", "", ""],
    ["TOTAL", "", "", "", "514,000 USD"]
]

t_products = Table(table_data, colWidths=[1.8*inch, 1.8*inch, 1.0*inch, 1.0*inch, 1.4*inch])
t_products.setStyle(TableStyle([
    ('BACKGROUND', (0,0), (-1,0), colors.lightgrey),
    ('TEXTCOLOR', (0,0), (-1,0), colors.black),
    ('ALIGN', (0,0), (-1,0), 'CENTER'),
    ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
    ('FONTSIZE', (0,0), (-1,0), 9),
    ('BOTTOMPADDING', (0,0), (-1,0), 8),
    # Content formatting
    ('ALIGN', (2,1), (-1,-2), 'RIGHT'), # Align numbers right
    ('ALIGN', (0,1), (1,-1), 'LEFT'),
    ('FONTNAME', (0,-1), (-1,-1), 'Helvetica-Bold'), # Total row bold
    ('GRID', (0,0), (-1,-1), 0.5, colors.black),
    ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
]))
elements.append(t_products)
elements.append(Spacer(1, 20))

# 4. Terms and Conditions
terms_text = [
    "<b>1. PORT OF LOADING:</b> XIAMEN, CHINA AND TIANJIN, CHINA",
    "<b>2. PORT OF DESTINATION:</b> SEMARANG, INDONESIA",
    "<b>3. PAYMENT TERM:</b> 100% T/T BEFORE SHIPMENT",
    "<b>4. DELIVERY TIME:</b> WITHIN 45 DAYS AFTER RECEIPT DEPOSIT",
    "&nbsp;&nbsp;&nbsp;&nbsp;<b>OFFER VALIDITY:</b> UNTIL 2025-11-07",
    "<b>5. COUNTRY OF ORIGIN:</b> CHINA",
    "<b>6. BENEFICIARY NAME:</b> QUANZHOU HAOZHAN IMPORT AND EXPORT TRADE CO., LTD",
    "<b>7. DETAILS OF BENEFICIARY BANK:</b>"
]

for term in terms_text:
    elements.append(Paragraph(term, normal_style))
    elements.append(Spacer(1, 4))

# Bank Details (Indented)
bank_details = [
    "<b>Beneficiary Bank:</b> AGRICULTURAL BANK OF CHINA, FUJIAN BR",
    "<b>Bank Address:</b> YINXING BUILDING, ZENGJING FINANCIAL DISTRICT, QINGYANG TOWN, JINJIANG, QUANZHOU CITY, FUJIAN PROVINCE, CHINA",
    "<b>SWIFT:</b> ABOCONB/130",
    "<b>Account No:</b> 5301140400..."
]

for detail in bank_details:
    elements.append(Paragraph(f"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{detail}", normal_style))

elements.append(Spacer(1, 30))

# 5. Signatures
signature_data = [
    [Paragraph("<b>THE BUYER</b>", title_style), Paragraph("<b>THE SELLER</b>", title_style)],
    [Paragraph("PT FUSHENG WEAVING INTERNATIONAL", title_style), Paragraph("QUANZHOU HAOZHAN IMPORT AND EXPORT TRADE CO., LTD", title_style)],
    ["\n\n\n\n", "\n\n\n\n"], # Space for signature
    [Paragraph("__________________________", title_style), Paragraph("__________________________", title_style)],
    [Paragraph("SIGNATURE", title_style), Paragraph("SIGNATURE", title_style)]
]

t_sig = Table(signature_data, colWidths=[3.5*inch, 3.5*inch])
t_sig.setStyle(TableStyle([
    ('ALIGN', (0,0), (-1,-1), 'CENTER'),
    ('VALIGN', (0,0), (-1,-1), 'TOP'),
]))
elements.append(t_sig)

# Build PDF
doc.build(elements)