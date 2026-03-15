-- Test data for classic-to-gutenberg E2E/smoke tests.
-- Post IDs start at 100 to avoid conflicts with WordPress defaults.

-- Post 100: Simple paragraphs (raw text, relies on wpautop)
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (100, 1, '2024-01-01 00:00:00', '2024-01-01 00:00:00',
'This is a simple paragraph of text.

This is a second paragraph with <strong>bold</strong> and <em>italic</em> formatting.

And a third paragraph with <a href="https://example.com">a link</a>.',
'Simple Paragraphs', 'publish', 'simple-paragraphs', 'post', '2024-01-01 00:00:00', '2024-01-01 00:00:00');

-- Post 101: Headings and mixed content
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (101, 1, '2024-01-02 00:00:00', '2024-01-02 00:00:00',
'<h2>First Section</h2>

Some introductory text here.

<h3>Subsection</h3>

More text after the subsection.

<h2>Second Section</h2>

Final paragraph.',
'Headings Mixed', 'publish', 'headings-mixed', 'post', '2024-01-02 00:00:00', '2024-01-02 00:00:00');

-- Post 102: Lists
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (102, 1, '2024-01-03 00:00:00', '2024-01-03 00:00:00',
'<ul>
<li>First item</li>
<li>Second item</li>
<li>Third item</li>
</ul>

<ol>
<li>Step one</li>
<li>Step two</li>
</ol>',
'Lists', 'publish', 'lists', 'post', '2024-01-03 00:00:00', '2024-01-03 00:00:00');

-- Post 103: Blockquote and separator
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (103, 1, '2024-01-04 00:00:00', '2024-01-04 00:00:00',
'<blockquote>
<p>To be or not to be.</p>
<cite>Shakespeare</cite>
</blockquote>

<hr />

Some text after the separator.',
'Quote and Separator', 'publish', 'quote-and-separator', 'post', '2024-01-04 00:00:00', '2024-01-04 00:00:00');

-- Post 104: Image and shortcodes
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (104, 1, '2024-01-05 00:00:00', '2024-01-05 00:00:00',
'<img src="https://example.com/photo.jpg" alt="A photo" class="aligncenter wp-image-50" width="640" height="480" />

[gallery ids="10,20,30" columns="3"]

[contact-form-7 id="99" title="Contact"]',
'Images and Shortcodes', 'publish', 'images-and-shortcodes', 'post', '2024-01-05 00:00:00', '2024-01-05 00:00:00');

-- Post 105: Table
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (105, 1, '2024-01-06 00:00:00', '2024-01-06 00:00:00',
'<table>
<thead>
<tr><th>Name</th><th>Role</th></tr>
</thead>
<tbody>
<tr><td>Alice</td><td>Developer</td></tr>
<tr><td>Bob</td><td>Designer</td></tr>
</tbody>
</table>',
'Table', 'publish', 'table-test', 'post', '2024-01-06 00:00:00', '2024-01-06 00:00:00');

-- Post 106: More and nextpage markers
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (106, 1, '2024-01-07 00:00:00', '2024-01-07 00:00:00',
'This is the excerpt.

<!--more-->

This is the rest of the post.

<!--nextpage-->

This is page two.',
'More and Nextpage', 'publish', 'more-and-nextpage', 'post', '2024-01-07 00:00:00', '2024-01-07 00:00:00');

-- Page 110: Classic page content
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (110, 1, '2024-01-10 00:00:00', '2024-01-10 00:00:00',
'<h2>About Us</h2>

We are a company that does things.

<ul>
<li>Service one</li>
<li>Service two</li>
<li>Service three</li>
</ul>

Contact us for more information.',
'About Page', 'publish', 'about-page', 'page', '2024-01-10 00:00:00', '2024-01-10 00:00:00');

-- Post 120: Already converted (has block markup) — should be skipped by finder
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (120, 1, '2024-01-20 00:00:00', '2024-01-20 00:00:00',
'<!-- wp:paragraph -->
<p>This post already has blocks.</p>
<!-- /wp:paragraph -->',
'Already Converted', 'publish', 'already-converted', 'post', '2024-01-20 00:00:00', '2024-01-20 00:00:00');

-- Post 121: Draft classic post — should be found by finder
INSERT INTO wp_posts (ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, post_modified, post_modified_gmt)
VALUES (121, 1, '2024-01-21 00:00:00', '2024-01-21 00:00:00',
'This is a draft post with classic content.

It has two paragraphs.',
'Draft Classic', 'draft', 'draft-classic', 'post', '2024-01-21 00:00:00', '2024-01-21 00:00:00');
