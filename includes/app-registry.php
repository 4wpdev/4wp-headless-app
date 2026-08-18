<?php

defined( 'ABSPATH' ) || exit;

function fwp_headless_app_get_apps() {
	return array(
		'portfolio-v1' => array(
			'name' => 'Portfolio v1 (Base)',
			'description' => 'Base portfolio app with Skills, Services, Experience, and Projects.',
			'seed' => array(
				'settings' => array(
					'site' => array(
						'title' => 'Anatoliy Dovgun - WordPress Developer',
						'tagline' => 'Full Stack WordPress Development',
						'description' => 'Experienced WordPress developer specializing in custom themes, plugins, and e-commerce solutions.',
					),
					'contact' => array(
						'email' => 'anatoliy@example.com',
						'phone' => '+380 XX XXX XXXX',
						'location' => 'Kyiv, Ukraine',
						'available' => true,
						'availability_text' => 'Available for new projects',
					),
					'social' => array(
						'github' => 'https://github.com/anatoliydovgun',
						'linkedin' => 'https://linkedin.com/in/anatoliydovgun',
						'facebook' => 'https://facebook.com/anatoliydovgun',
						'wordpress' => 'https://profiles.wordpress.org/anatoliydovgun',
					),
					'stats' => array(
						'years_experience' => 8,
						'projects_completed' => 150,
						'happy_clients' => 50,
						'client_satisfaction' => 99,
					),
					'theme' => array(
						'primary_color' => '#3b82f6',
						'secondary_color' => '#06b6d4',
						'accent_color' => '#a855f7',
					),
				),
				'skills' => array(
					array(
						'id' => 1,
						'name' => 'WordPress',
						'slug' => 'wordpress',
						'icon' => 'Code2',
						'color' => 'from-blue-500 to-blue-600',
						'level' => 98,
						'description' => 'Expert in WordPress core, themes, and plugin development',
					),
					array(
						'id' => 2,
						'name' => 'PHP',
						'slug' => 'php',
						'icon' => 'Server',
						'color' => 'from-indigo-500 to-purple-500',
						'level' => 95,
						'description' => 'Advanced PHP development and OOP patterns',
					),
					array(
						'id' => 3,
						'name' => 'Gutenberg',
						'slug' => 'gutenberg',
						'icon' => 'Blocks',
						'color' => 'from-cyan-500 to-blue-500',
						'level' => 92,
						'description' => 'Custom blocks and FSE development',
					),
					array(
						'id' => 4,
						'name' => 'React',
						'slug' => 'react',
						'icon' => 'Zap',
						'color' => 'from-cyan-400 to-cyan-600',
						'level' => 88,
						'description' => 'Modern React development with hooks and state management',
					),
					array(
						'id' => 5,
						'name' => 'WooCommerce',
						'slug' => 'woocommerce',
						'icon' => 'ShoppingCart',
						'color' => 'from-purple-500 to-pink-500',
						'level' => 94,
						'description' => 'E-commerce solutions and custom extensions',
					),
					array(
						'id' => 6,
						'name' => 'REST API',
						'slug' => 'rest-api',
						'icon' => 'Database',
						'color' => 'from-green-500 to-emerald-500',
						'level' => 90,
						'description' => 'Custom endpoints and API integrations',
					),
					array(
						'id' => 7,
						'name' => 'ACF',
						'slug' => 'acf',
						'icon' => 'Palette',
						'color' => 'from-pink-500 to-rose-500',
						'level' => 96,
						'description' => 'Advanced Custom Fields and flexible content',
					),
					array(
						'id' => 8,
						'name' => 'FSE',
						'slug' => 'fse',
						'icon' => 'Globe',
						'color' => 'from-orange-500 to-amber-500',
						'level' => 85,
						'description' => 'Full Site Editing and block themes',
					),
					array(
						'id' => 9,
						'name' => 'Performance',
						'slug' => 'performance',
						'icon' => 'Gauge',
						'color' => 'from-red-500 to-orange-500',
						'level' => 91,
						'description' => 'Optimization, caching, and speed improvements',
					),
					array(
						'id' => 10,
						'name' => 'SEO',
						'slug' => 'seo',
						'icon' => 'Search',
						'color' => 'from-teal-500 to-cyan-500',
						'level' => 87,
						'description' => 'Technical SEO and schema markup',
					),
				),
				'services' => array(
					array(
						'id' => 1,
						'title' => 'Custom WordPress Development',
						'slug' => 'custom-wordpress-development',
						'icon' => 'Code2',
						'color' => 'from-blue-500 to-cyan-500',
						'description' => 'Tailored WordPress solutions built from scratch to meet your unique business requirements.',
						'features' => array(
							'Custom theme development',
							'Plugin architecture',
							'Performance optimization',
							'Security hardening',
						),
					),
					array(
						'id' => 2,
						'title' => 'WooCommerce Solutions',
						'slug' => 'woocommerce-solutions',
						'icon' => 'ShoppingCart',
						'color' => 'from-purple-500 to-pink-500',
						'description' => 'Complete e-commerce platforms with custom features, payment gateways, and shipping integrations.',
						'features' => array(
							'Custom product types',
							'Payment gateway integration',
							'Inventory management',
							'Shipping & taxes',
						),
					),
					array(
						'id' => 3,
						'title' => 'Gutenberg Block Development',
						'slug' => 'gutenberg-block-development',
						'icon' => 'Blocks',
						'color' => 'from-cyan-500 to-blue-500',
						'description' => 'Custom Gutenberg blocks and Full Site Editing themes for modern WordPress experiences.',
						'features' => array(
							'Custom blocks',
							'FSE themes',
							'Block patterns',
							'Dynamic content',
						),
					),
					array(
						'id' => 4,
						'title' => 'API & Headless WordPress',
						'slug' => 'api-headless-wordpress',
						'icon' => 'Globe',
						'color' => 'from-green-500 to-emerald-500',
						'description' => 'Decoupled WordPress with React/Next.js frontends and custom REST/GraphQL APIs.',
						'features' => array(
							'Custom REST endpoints',
							'GraphQL integration',
							'React/Next.js frontend',
							'Authentication & security',
						),
					),
					array(
						'id' => 5,
						'title' => 'Performance Optimization',
						'slug' => 'performance-optimization',
						'icon' => 'Zap',
						'color' => 'from-orange-500 to-red-500',
						'description' => 'Speed up your WordPress site with caching, CDN, database optimization, and code improvements.',
						'features' => array(
							'Caching strategies',
							'Database optimization',
							'Image optimization',
							'Core Web Vitals',
						),
					),
					array(
						'id' => 6,
						'title' => 'Maintenance & Support',
						'slug' => 'maintenance-support',
						'icon' => 'Settings',
						'color' => 'from-indigo-500 to-purple-500',
						'description' => 'Ongoing WordPress maintenance, updates, security monitoring, and technical support.',
						'features' => array(
							'Regular updates',
							'Security monitoring',
							'Backup management',
							'Bug fixes & support',
						),
					),
				),
				'experience' => array(
					array(
						'id' => 1,
						'role' => 'Senior WordPress Developer',
						'company' => 'Tech Solutions Agency',
						'location' => 'Remote',
						'period' => '2021 - Present',
						'start_date' => '2021-01-01',
						'end_date' => null,
						'description' => 'Leading development of enterprise WordPress solutions, custom plugins, and headless architectures.',
						'technologies' => array( 'WordPress', 'React', 'REST API', 'WooCommerce' ),
						'current' => true,
					),
					array(
						'id' => 2,
						'role' => 'Full Stack Developer',
						'company' => 'Digital Innovations Inc',
						'location' => 'Kyiv, Ukraine',
						'period' => '2019 - 2021',
						'start_date' => '2019-03-01',
						'end_date' => '2021-01-01',
						'description' => 'Developed custom themes, plugins, and e-commerce solutions for international clients.',
						'technologies' => array( 'PHP', 'JavaScript', 'ACF', 'Gutenberg' ),
						'current' => false,
					),
					array(
						'id' => 3,
						'role' => 'WordPress Developer',
						'company' => 'Creative Web Studio',
						'location' => 'Remote',
						'period' => '2017 - 2019',
						'start_date' => '2017-06-01',
						'end_date' => '2019-03-01',
						'description' => 'Built responsive websites and custom themes for small to medium businesses.',
						'technologies' => array( 'WordPress', 'CSS', 'jQuery', 'PHP' ),
						'current' => false,
					),
					array(
						'id' => 4,
						'role' => 'Junior Web Developer',
						'company' => 'StartUp Hub',
						'location' => 'Lviv, Ukraine',
						'period' => '2016 - 2017',
						'start_date' => '2016-01-01',
						'end_date' => '2017-06-01',
						'description' => 'Started career building WordPress sites and learning modern development practices.',
						'technologies' => array( 'HTML', 'CSS', 'JavaScript', 'WordPress' ),
						'current' => false,
					),
				),
				'projects' => array(
					array(
						'id' => 1,
						'title' => 'Premium E-Commerce Store',
						'slug' => 'premium-ecommerce-store',
						'category' => 'ecommerce',
						'description' => 'Custom WooCommerce store with advanced filtering, wishlists, and personalized recommendations.',
						'image' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=800&h=600&fit=crop',
						'technologies' => array( 'WooCommerce', 'React', 'Custom API', 'Payment Gateway' ),
						'link' => 'https://example.com',
						'github' => 'https://github.com',
						'featured' => true,
					),
					array(
						'id' => 2,
						'title' => 'Corporate Website Redesign',
						'slug' => 'corporate-website-redesign',
						'category' => 'corporate',
						'description' => 'Modern corporate site with custom Gutenberg blocks, multilingual support, and advanced SEO.',
						'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&h=600&fit=crop',
						'technologies' => array( 'Gutenberg', 'ACF', 'WPML', 'Performance' ),
						'link' => 'https://example.com',
						'github' => null,
						'featured' => true,
					),
					array(
						'id' => 3,
						'title' => 'Magazine & Blog Platform',
						'slug' => 'magazine-blog-platform',
						'category' => 'blog',
						'description' => 'High-performance blog with custom post types, advanced search, and social integrations.',
						'image' => 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800&h=600&fit=crop',
						'technologies' => array( 'Custom Theme', 'ElasticSearch', 'REST API', 'Social Login' ),
						'link' => 'https://example.com',
						'github' => 'https://github.com',
						'featured' => false,
					),
					array(
						'id' => 4,
						'title' => 'Membership & LMS System',
						'slug' => 'membership-lms-system',
						'category' => 'custom',
						'description' => 'Complete learning management system with courses, quizzes, certificates, and payment integration.',
						'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800&h=600&fit=crop',
						'technologies' => array( 'LearnDash', 'Custom Plugin', 'Stripe', 'Certificates' ),
						'link' => 'https://example.com',
						'github' => null,
						'featured' => true,
					),
					array(
						'id' => 5,
						'title' => 'Real Estate Listings',
						'slug' => 'real-estate-listings',
						'category' => 'custom',
						'description' => 'Property listings platform with map integration, advanced filters, and lead generation.',
						'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=600&fit=crop',
						'technologies' => array( 'Custom CPT', 'Google Maps', 'Advanced Search', 'CRM' ),
						'link' => 'https://example.com',
						'github' => 'https://github.com',
						'featured' => false,
					),
					array(
						'id' => 6,
						'title' => 'Restaurant Chain Website',
						'slug' => 'restaurant-chain-website',
						'category' => 'corporate',
						'description' => 'Multi-location restaurant site with online ordering, reservations, and menu management.',
						'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop',
						'technologies' => array( 'WooCommerce', 'Bookings', 'Multi-site', 'Custom Admin' ),
						'link' => 'https://example.com',
						'github' => null,
						'featured' => false,
					),
				),
				'categories' => array(
					array( 'id' => 1, 'name' => 'All', 'slug' => 'all' ),
					array( 'id' => 2, 'name' => 'E-Commerce', 'slug' => 'ecommerce' ),
					array( 'id' => 3, 'name' => 'Corporate', 'slug' => 'corporate' ),
					array( 'id' => 4, 'name' => 'Blog', 'slug' => 'blog' ),
					array( 'id' => 5, 'name' => 'Custom', 'slug' => 'custom' ),
				),
			),
		),
		'portfolio-developer' => array(
			'name' => 'Portfolio: Developer',
			'description' => 'Developer-focused portfolio with technical skills and project emphasis.',
			'inherits' => 'portfolio-v1',
			'theme_json' => array(
				'version' => 2,
				'settings' => array(
					'color' => array(
						'gradients' => array(
							array(
								'name' => 'Developer Blue',
								'slug' => 'dev-blue',
								'gradient' => 'linear-gradient(135deg, #3b82f6, #06b6d4)',
							),
							array(
								'name' => 'Neon Purple',
								'slug' => 'neon-purple',
								'gradient' => 'linear-gradient(135deg, #8b5cf6, #a855f7)',
							),
							array(
								'name' => 'Matrix Green',
								'slug' => 'matrix-green',
								'gradient' => 'linear-gradient(135deg, #22c55e, #10b981)',
							),
						),
					),
				),
			),
		),
		'portfolio-dancer' => array(
			'name' => 'Portfolio: Dancer',
			'description' => 'Performance portfolio focused on performances, styles, and media.',
			'inherits' => 'portfolio-v1',
		),
		'portfolio-trainer' => array(
			'name' => 'Portfolio: Trainer',
			'description' => 'Trainer portfolio with programs, certifications, and coaching focus.',
			'inherits' => 'portfolio-v1',
		),
		'grv-build' => array(
			'name'              => 'GRV Build',
			'description'       => 'Construction company — work_item, team, faq, catalog_line, geo_area.',
			'seed_file'         => 'grv-export.json',
			'content_model_key' => 'headless-site',
		),
	);
}

function fwp_headless_app_get_app_seed( $app_key ) {
	$apps = fwp_headless_app_get_apps();
	if ( empty( $app_key ) || empty( $apps[ $app_key ] ) ) {
		return array();
	}

	$app = $apps[ $app_key ];
	if ( ! empty( $app['seed'] ) ) {
		return $app['seed'];
	}

	if ( ! empty( $app['inherits'] ) && ! empty( $apps[ $app['inherits'] ]['seed'] ) ) {
		return $apps[ $app['inherits'] ]['seed'];
	}

	if ( ! empty( $app['seed_file'] ) ) {
		$path = dirname( __DIR__ ) . '/seeds/' . $app['seed_file'];
		if ( file_exists( $path ) ) {
			$data = json_decode( file_get_contents( $path ), true );
			return is_array( $data ) ? $data : array();
		}
	}

	return array();
}

function fwp_headless_app_get_app_theme_json( $app_key ) {
	$apps = fwp_headless_app_get_apps();
	if ( empty( $app_key ) || empty( $apps[ $app_key ] ) ) {
		return array();
	}

	$app = $apps[ $app_key ];
	if ( ! empty( $app['theme_json'] ) ) {
		return $app['theme_json'];
	}

	if ( ! empty( $app['inherits'] ) && ! empty( $apps[ $app['inherits'] ]['theme_json'] ) ) {
		return $apps[ $app['inherits'] ]['theme_json'];
	}

	return array();
}

