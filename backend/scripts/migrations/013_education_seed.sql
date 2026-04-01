-- Education Articles Seed Data
-- Populates education_articles table with content

INSERT IGNORE INTO education_articles (slug, title, category, content_html, published_at, updated_at) VALUES
('what-is-ra', 'What is Rheumatoid Arthritis?', 'WHAT_IS_RA', 
'<div style="padding: 20px; font-family: sans-serif;">
<h2>What is Rheumatoid Arthritis?</h2>
<p>Rheumatoid Arthritis (RA) is a chronic autoimmune disease that causes inflammation in the joints and can affect other parts of the body.</p>
<h3>Key Points:</h3>
<ul>
<li><strong>Autoimmune disorder:</strong> Your immune system mistakenly attacks your own body tissues</li>
<li><strong>Affects joints symmetrically:</strong> Usually affects the same joints on both sides of the body</li>
<li><strong>Can cause permanent joint damage:</strong> If left untreated, RA can lead to joint deformity and disability</li>
<li><strong>Early treatment is crucial:</strong> Starting treatment early can prevent joint damage and improve outcomes</li>
</ul>
<h3>Symptoms:</h3>
<ul>
<li>Joint pain, swelling, and stiffness</li>
<li>Fatigue and weakness</li>
<li>Morning stiffness lasting more than 30 minutes</li>
<li>Low-grade fever</li>
<li>Loss of appetite</li>
</ul>
<h3>Risk Factors:</h3>
<ul>
<li>Age (most common between 40-60)</li>
<li>Gender (women are more likely to develop RA)</li>
<li>Family history</li>
<li>Smoking</li>
<li>Obesity</li>
</ul>
</div>', NOW(), NOW()),

('nutrition-tips', 'Nutrition for RA Patients', 'NUTRITION',
'<div style="padding: 20px; font-family: sans-serif;">
<h2>Nutrition for RA Patients</h2>
<p>Proper nutrition plays a vital role in managing RA symptoms and overall health. A balanced diet can help reduce inflammation and support your treatment plan.</p>
<h3>Recommended Foods:</h3>
<ul>
<li><strong>Omega-3 rich fish:</strong> Salmon, mackerel, sardines (2-3 times per week)</li>
<li><strong>Fresh fruits and vegetables:</strong> Berries, leafy greens, tomatoes, bell peppers</li>
<li><strong>Whole grains:</strong> Brown rice, quinoa, oats, whole wheat</li>
<li><strong>Lean proteins:</strong> Chicken, turkey, beans, lentils</li>
<li><strong>Nuts and seeds:</strong> Walnuts, almonds, chia seeds, flaxseeds</li>
<li><strong>Olive oil:</strong> Use as primary cooking oil</li>
</ul>
<h3>Foods to Limit or Avoid:</h3>
<ul>
<li>Processed foods and fast food</li>
<li>Sugary drinks and snacks</li>
<li>Red meat (limit to once per week)</li>
<li>Fried foods</li>
<li>Excessive alcohol</li>
</ul>
<h3>Anti-Inflammatory Diet Tips:</h3>
<ul>
<li>Eat a variety of colorful fruits and vegetables daily</li>
<li>Include fish in your diet 2-3 times per week</li>
<li>Choose whole grains over refined grains</li>
<li>Stay hydrated with water and herbal teas</li>
<li>Consider supplements like Vitamin D and Omega-3 (consult your doctor first)</li>
</ul>
</div>', NOW(), NOW()),

('lifestyle', 'Lifestyle Management', 'LIFESTYLE',
'<div style="padding: 20px; font-family: sans-serif;">
<h2>Lifestyle Management for RA</h2>
<p>Maintaining a healthy lifestyle can significantly improve RA symptoms and quality of life. Small changes can make a big difference.</p>
<h3>Exercise and Physical Activity:</h3>
<ul>
<li><strong>Low-impact exercises:</strong> Walking, swimming, cycling, yoga</li>
<li><strong>Range of motion exercises:</strong> Daily stretching to maintain joint flexibility</li>
<li><strong>Strength training:</strong> Light weights to maintain muscle strength</li>
<li><strong>Balance exercises:</strong> To prevent falls and improve stability</li>
</ul>
<h3>Rest and Sleep:</h3>
<ul>
<li>Aim for 7-9 hours of quality sleep per night</li>
<li>Take rest breaks during the day when needed</li>
<li>Use proper sleep positions to reduce joint stress</li>
<li>Create a comfortable sleep environment</li>
</ul>
<h3>Stress Management:</h3>
<ul>
<li>Practice relaxation techniques (meditation, deep breathing)</li>
<li>Engage in hobbies and activities you enjoy</li>
<li>Consider counseling or support groups</li>
<li>Maintain social connections</li>
</ul>
<h3>Joint Protection:</h3>
<ul>
<li>Use assistive devices when needed (jar openers, ergonomic tools)</li>
<li>Avoid repetitive motions</li>
<li>Take breaks during activities</li>
<li>Use proper body mechanics when lifting</li>
</ul>
<h3>Smoking Cessation:</h3>
<p>Smoking can worsen RA symptoms and reduce treatment effectiveness. If you smoke, consider quitting with support from your healthcare team.</p>
</div>', NOW(), NOW()),

('managing-symptoms', 'Managing Your Symptoms', 'MANAGEMENT',
'<div style="padding: 20px; font-family: sans-serif;">
<h2>Managing Your RA Symptoms</h2>
<p>Effective symptom management is key to living well with RA. Work with your healthcare team to develop a comprehensive management plan.</p>
<h3>Medication Adherence:</h3>
<ul>
<li>Take medications exactly as prescribed</li>
<li>Don''t skip doses, even when feeling well</li>
<li>Keep a medication log to track intake</li>
<li>Report any side effects to your doctor immediately</li>
<li>Never stop medications without consulting your doctor</li>
</ul>
<h3>Physical Therapy:</h3>
<ul>
<li>Work with a physical therapist to develop an exercise program</li>
<li>Learn proper techniques for joint protection</li>
<li>Use heat and cold therapy as recommended</li>
<li>Practice exercises at home regularly</li>
</ul>
<h3>Pain Management:</h3>
<ul>
<li><strong>Heat therapy:</strong> Warm baths, heating pads for stiffness</li>
<li><strong>Cold therapy:</strong> Ice packs for acute inflammation</li>
<li><strong>Massage:</strong> Gentle massage can help reduce muscle tension</li>
<li><strong>Relaxation techniques:</strong> Deep breathing, meditation</li>
</ul>
<h3>Monitoring Symptoms:</h3>
<ul>
<li>Keep a symptom diary to track patterns</li>
<li>Note triggers that worsen symptoms</li>
<li>Monitor joint swelling and pain levels</li>
<li>Report changes to your doctor</li>
</ul>
<h3>When to Contact Your Doctor:</h3>
<ul>
<li>New or worsening symptoms</li>
<li>Signs of infection (fever, chills)</li>
<li>Medication side effects</li>
<li>Difficulty managing daily activities</li>
<li>Emotional distress or depression</li>
</ul>
</div>', NOW(), NOW());

