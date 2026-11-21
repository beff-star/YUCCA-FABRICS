<?php
// Presentation for the services stored in the `services` table, keyed by slug. The database keeps the
// admin-editable name, price, duration and short description; this file adds the look and longer copy.
// A service added in admin without an entry here falls back to 'default'. Images: assets/images/services/.
// Themes pair a background with a text colour that passes WCAG AA (mustard uses dark text).
return [
    'furniture-reupholstery' => [
        'theme' => ['bg' => '#1F5B43', 'text' => 'light'],
        'icon' => 'fa-couch',
        'image' => 'reupholstery-tufted-corduroy.jpg',
        'image_alt' => 'Close-up of a green corduroy seat with tufted buttons',
        'tagline' => 'Keep the frame you love. Change everything else.',
        'story' => 'Good furniture is often worth more than it looks. We take a tired sofa, armchair or dining chair back to its frame, put right what has worn out underneath, and dress it in a fabric chosen for your room and the way you use it.',
        'includes' => ['Help choosing from our bouclé, linen, velvet and leather collection', 'Old covers stripped and the frame checked', 'Foam, webbing and padding renewed where needed', 'Neat finishing: piping, buttons and trims'],
        'ideal' => 'Sofas, armchairs, dining and accent chairs, headboards and ottomans.',
    ],
    'cushion-renewal' => [
        'theme' => ['bg' => '#E3A82B', 'text' => 'dark'],
        'icon' => 'fa-mattress-pillow',
        'image' => 'cushion-renewal-mustard-cushion.jpg',
        'image_alt' => 'A mustard cushion resting on an upholstered armchair',
        'tagline' => 'Plump, fresh and comfortable again.',
        'story' => 'Flat seats and faded covers make a whole sofa look older than it is. New covers and fresh inners bring back the shape and comfort, often for far less than replacing the piece.',
        'includes' => ['New covers in a fabric of your choice', 'Flattened foam and inners replaced', 'Zips, piping and tailored corners', 'Matching scatter cushions to pull a room together'],
        'ideal' => 'Sofa seat and back cushions, scatter cushions, window seats and bench pads.',
    ],
    'fabric-colour-consultation' => [
        'theme' => ['bg' => '#16646E', 'text' => 'light'],
        'icon' => 'fa-swatchbook',
        'image' => 'fabric-consultation-samples.jpg',
        'image_alt' => 'Hands comparing colourful fabric samples on a table',
        'tagline' => 'Find the fabric that fits your life.',
        'story' => 'Choosing from a small square on a screen is hard. We bring the samples, talk through how each one feels, wears and cleans, and help you match colour and texture to the room you already have.',
        'includes' => ['Compare textures and weaves side by side', 'Colours coordinated with your walls, floors and furniture', 'Practical advice for children, pets and busy rooms', 'A shortlist you can order from with confidence'],
        'ideal' => 'Anyone choosing fabric for a sofa, curtains, cushions or a full room.',
        'consultation_type' => 'Fabric and colour selection',
    ],
    'room-styling-consultation' => [
        'theme' => ['bg' => '#A9461E', 'text' => 'light'],
        'icon' => 'fa-compass-drafting',
        'image' => 'room-styling-swatches-plans.jpg',
        'image_alt' => 'A hand holding colour swatches over room plans',
        'tagline' => 'A clear plan for a room that works.',
        'story' => 'Sometimes the furniture is fine but the room still doesn’t feel right. In one focused session we look at layout, colour, fabric and the finishing touches, and leave you with practical next steps.',
        'includes' => ['Furniture layout ideas for flow and comfort', 'A colour palette with fabric pairings', 'Curtains, cushions, rugs and lighting suggestions', 'Priorities to suit your budget and timeline'],
        'ideal' => 'Living rooms, bedrooms, home offices and small commercial spaces.',
        'consultation_type' => 'Residential interior design',
    ],
    'default' => [
        'theme' => ['bg' => '#3F5320', 'text' => 'light'],
        'icon' => 'fa-wand-magic-sparkles',
        'image' => 'upholstery-workshop-chair.jpg',
        'image_alt' => 'An upholstered chair in a workshop',
        'tagline' => '',
        'story' => '',
        'includes' => [],
        'ideal' => '',
        'consultation_type' => '',
    ],
];
