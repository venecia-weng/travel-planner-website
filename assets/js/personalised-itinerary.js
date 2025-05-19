// Fixed and properly structured version of personalised-itinerary.js
let currentDestination = null;

// Helper function to get currency symbol
function getCurrencySymbol(currency) {
    const symbols = {
        'THB': '฿',
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
    };
    return symbols[currency] || '';
}

// Function to update budget descriptions based on current destination
function updateBudgetDescriptions() {
    if (!currentDestination) {
        resetBudgetDescriptions();
        return;
    }
    
    const budgetContainer = document.getElementById('budgetDescriptions');
    if (!budgetContainer) {
        console.error("Budget descriptions container not found");
        return;
    }
    
    // Clear previous content
    budgetContainer.innerHTML = '';
    
    // Create budget description divs
    const budgetLevels = [
        { 
            level: 1, 
            name: 'Budget Friendly',
            minAmount: currentDestination.budget_low_min,
            maxAmount: currentDestination.budget_low_max,
            description: currentDestination.budget_low_desc || 'Hostels, budget hotels, street food and local eateries, public transportation, free/low-cost activities.'
        },
        { 
            level: 2, 
            name: 'Comfort on a Budget',
            minAmount: currentDestination.budget_low_max,
            maxAmount: currentDestination.budget_mid_min,
            description: 'Nicer hostels, budget hotels, mix of street food and casual restaurants, public transport with occasional taxis, mix of free and paid activities.'
        },
        { 
            level: 3, 
            name: 'Mid-range',
            minAmount: currentDestination.budget_mid_min,
            maxAmount: currentDestination.budget_mid_max,
            description: currentDestination.budget_mid_desc || '3-star hotels, casual restaurants with some nicer dining experiences, mix of public and private transportation, paid activities and attractions.'
        },
        { 
            level: 4, 
            name: 'Upper Mid-range',
            minAmount: currentDestination.budget_mid_max,
            maxAmount: currentDestination.budget_high_min,
            description: '4-star hotels, good restaurants with occasional fine dining, private transportation, premium activities and experiences.'
        },
        { 
            level: 5, 
            name: 'Luxury',
            minAmount: currentDestination.budget_high_min,
            maxAmount: currentDestination.budget_high_max,
            description: currentDestination.budget_high_desc || '5-star hotels and resorts, fine dining, private transportation and guided tours, exclusive experiences and activities.'
        }
    ];
    
    budgetLevels.forEach(level => {
        const budgetDiv = document.createElement('div');
        budgetDiv.id = `budget-${level.level}`;
        budgetDiv.className = 'budget-description';
        budgetDiv.style.display = level.level === 3 ? 'block' : 'none'; // Show mid-range by default
        
        const currency = currentDestination.currency || '';
        const currencySymbol = getCurrencySymbol(currency);
        
        let budgetRange = '';
        if (level.minAmount && level.maxAmount) {
            budgetRange = `${currencySymbol}${level.minAmount.toLocaleString()}-${level.maxAmount.toLocaleString()} ${currency} per day per person`;
        } else if (level.minAmount) {
            budgetRange = `${currencySymbol}${level.minAmount.toLocaleString()}+ ${currency} per day per person`;
        } else {
            budgetRange = `Varies by location`;
        }
        
        budgetDiv.innerHTML = `
            <p><strong>${level.name}:</strong> ${level.description}</p>
            <p><strong>Approximate daily budget:</strong> ${budgetRange}</p>
        `;
        
        budgetContainer.appendChild(budgetDiv);
    });
}

// Function to reset budget descriptions to generic options
function resetBudgetDescriptions() {
    const budgetContainer = document.getElementById('budgetDescriptions');
    if (!budgetContainer) {
        console.error("Budget descriptions container not found");
        return;
    }
    
    // Clear previous content
    budgetContainer.innerHTML = '';
    
    // Create generic budget descriptions
    const genericLevels = [
        { 
            level: 1, 
            name: 'Budget Friendly',
            description: 'Hostels, budget hotels, street food and local eateries, public transportation, free/low-cost activities.'
        },
        { 
            level: 2, 
            name: 'Comfort on a Budget',
            description: 'Nicer hostels, budget hotels, mix of street food and casual restaurants, public transport with occasional taxis, mix of free and paid activities.'
        },
        { 
            level: 3, 
            name: 'Mid-range',
            description: '3-star hotels, casual restaurants with some nicer dining experiences, mix of public and private transportation, paid activities and attractions.'
        },
        { 
            level: 4, 
            name: 'Upper Mid-range',
            description: '4-star hotels, good restaurants with occasional fine dining, private transportation, premium activities and experiences.'
        },
        { 
            level: 5, 
            name: 'Luxury',
            description: '5-star hotels and resorts, fine dining, private transportation and guided tours, exclusive experiences and activities.'
        }
    ];
    
    genericLevels.forEach(level => {
        const budgetDiv = document.createElement('div');
        budgetDiv.id = `budget-${level.level}`;
        budgetDiv.className = 'budget-description';
        budgetDiv.style.display = level.level === 3 ? 'block' : 'none'; // Show mid-range by default
        
        budgetDiv.innerHTML = `
            <p><strong>${level.name}:</strong> ${level.description}</p>
            <p><strong>Approximate daily budget:</strong> Varies by destination</p>
        `;
        
        budgetContainer.appendChild(budgetDiv);
    });
}

// Validate selections before proceeding to next slide
function validateStep(step) {
    console.log("Validating step:", step);
    const currentSlide = document.querySelector(`.quiz-slide[data-step="${step}"]`);
    if (!currentSlide) {
        console.error(`Slide with data-step="${step}" not found`);
        return false;
    }
    
    // For option card selection slides
    const optionCards = currentSlide.querySelectorAll('.option-card');
    console.log(`Found ${optionCards.length} option cards on slide ${step}`);
    
    if (optionCards.length > 0) {
        const selectedCard = currentSlide.querySelector('.option-card.selected');
        console.log("Selected card:", selectedCard);
        
        if (!selectedCard) {
            console.log("No card selected on step", step);
            return false;
        }
        
        // For specific date selection slide
        if (step === 6) {
            const specificDatesCard = currentSlide.querySelector('.option-card[data-value="specific-dates"].selected');
            if (specificDatesCard) {
                const startDate = document.getElementById('startDate');
                const endDate = document.getElementById('endDate');
                
                if (!startDate || !endDate) {
                    console.error("Date input fields not found");
                    return false;
                }
                
                if (!startDate.value || !endDate.value) {
                    alert('Please select both start and end dates.');
                    return false;
                }
            }
        }
    }
    
    return true;
}



// Get a random image for a destination category
function getRandomImage(category) {
    // Define image collections for each traveler type
    const imageCollections = {
        'culture': [
            'assets/images/activities/temples.jpg',
            'assets/images/activities/museum.jpg',
            'assets/images/activities/historical-site.jpg',
            'assets/images/activities/local-market.jpg',
            'assets/images/activities/traditional-dance.jpg'
        ],
        'adventure': [
            'assets/images/activities/hiking.jpg',
            'assets/images/activities/kayaking.jpg',
            'assets/images/activities/scuba-diving.jpg',
            'assets/images/activities/zip-line.jpg',
            'assets/images/activities/mountain-biking.jpg'
        ],
        'relax': [
            'assets/images/activities/beach.jpg',
            'assets/images/activities/spa.jpg',
            'assets/images/activities/resort-pool.jpg',
            'assets/images/activities/meditation.jpg',
            'assets/images/activities/sunset-view.jpg'
        ],
        'foodie': [
            'assets/images/activities/street-food.jpg',
            'assets/images/activities/cooking-class.jpg',
            'assets/images/activities/fine-dining.jpg',
            'assets/images/activities/food-market.jpg',
            'assets/images/activities/local-cuisine.jpg'
        ],
        'nightlife': [
            'assets/images/activities/nightclub.jpg',
            'assets/images/activities/rooftop-bar.jpg',
            'assets/images/activities/night-market.jpg',
            'assets/images/activities/live-music.jpg',
            'assets/images/activities/beach-party.jpg'
        ],
        'balanced': [
            'assets/images/activities/city-tour.jpg',
            'assets/images/activities/shopping.jpg',
            'assets/images/activities/boat-tour.jpg',
            'assets/images/activities/wildlife.jpg',
            'assets/images/activities/cultural-show.jpg'
        ]
    };
    
    // Default to balanced if category not found
    const images = imageCollections[category] || imageCollections['balanced'];
    
    // Return a random image from the collection
    return images[Math.floor(Math.random() * images.length)];
}

// Generate day-by-day itinerary HTML
function generateDayByDayItinerary(selections, recommendations) {
    // Determine number of days based on duration
    let daysCount = 3; // Default
    
    switch(selections.duration) {
        case 'weekend':
            daysCount = 2;
            break;
        case 'short':
            daysCount = 4;
            break;
        case 'week':
            daysCount = 7;
            break;
        case 'twoweeks':
            daysCount = 10; // Show just a sample of days for two weeks
            break;
        case 'extended':
            daysCount = 10; // Show just a sample of days for extended stays
            break;
    }
    
    let itineraryHTML = '<div class="day-by-day-itinerary mt-4">';
    itineraryHTML += '<h4 class="text-center mb-4">Sample Day-by-Day Itinerary</h4>';
    itineraryHTML += '<div class="timeline">';
    
    // Extract recommended attractions and activities
    const recommendedActivities = recommendations.activities || [];
    
    // Sample activities based on traveler type (for filling in days)
    const activitySuggestions = {
        'culture': [
            'Visit local temples and historical sites',
            'Explore the city museum',
            'Take a guided cultural tour',
            'Attend a traditional performance',
            'Visit local markets and craft shops',
            'Learn about local traditions at a cultural center',
            'Explore ancient ruins and monuments',
            'Take part in a traditional ceremony'
        ],
        'adventure': [
            'Go hiking in the nearby mountains',
            'Try zip-lining through the jungle',
            'Join a kayaking expedition',
            'Go rock climbing or abseiling',
            'Explore caves and underground rivers',
            'Try snorkeling or scuba diving',
            'Rent a mountain bike and explore trails',
            'Join a white water rafting trip'
        ],
        'relax': [
            'Enjoy a day at the beach',
            'Book a spa treatment and massage',
            'Practice yoga at a beachfront class',
            'Relax by the pool with a good book',
            'Take a sunset cruise',
            'Visit hot springs for natural relaxation',
            'Enjoy meditation and mindfulness activities',
            'Spend time in nature with light walking'
        ],
        'foodie': [
            'Take a cooking class to learn local dishes',
            'Join a street food tour',
            'Visit local markets for fresh ingredients',
            'Enjoy dinner at a renowned local restaurant',
            'Take part in a food and wine pairing',
            'Visit local farms or food producers',
            'Join a coffee or tea tasting experience',
            'Learn to make local desserts and snacks'
        ],
        'nightlife': [
            'Experience the local night market',
            'Enjoy live music at popular venues',
            'Visit rooftop bars with city views',
            'Join a pub crawl to discover local bars',
            'Experience a cultural night show',
            'Dance at popular nightclubs',
            'Enjoy a sunset dinner with evening entertainment',
            'Take an evening river cruise with entertainment'
        ],
        'balanced': [
            'Explore popular tourist attractions',
            'Visit cultural and historical sites',
            'Enjoy some shopping at local markets',
            'Relax at a beach or natural area',
            'Try local cuisine at recommended restaurants',
            'Take a guided tour of the area',
            'Enjoy some leisure time at cafes or parks',
            'Experience a mix of culture and relaxation'
        ]
    };
    
    // Meal suggestions to intersperse
    const mealSuggestions = {
        'breakfast': [
            'Enjoy breakfast at your hotel',
            'Try a local breakfast spot',
            'Have breakfast at a beachfront cafe',
            'Grab a quick breakfast to start the day',
            'Experience a traditional breakfast'
        ],
        'lunch': [
            'Lunch at a local restaurant',
            'Enjoy a street food lunch',
            'Pack a picnic lunch',
            'Try a lunch special at a recommended spot',
            'Experience authentic cuisine for lunch'
        ],
        'dinner': [
            'Dinner at a scenic restaurant',
            'Enjoy a beachfront dinner',
            'Try the night market for dinner',
            'Experience fine dining for dinner',
            'Enjoy dinner with a cultural show'
        ]
    };
    
    // Evening activities
    const eveningActivities = [
        'Relax at your accommodation',
        'Take an evening stroll to explore',
        'Enjoy drinks at a local bar',
        'Watch the sunset',
        'Experience the local nightlife',
        'Attend a cultural performance'
    ];
    
    // Transportation suggestions
    const transportSuggestions = [
        'Transfer to your next destination',
        'Take a scenic drive to',
        'Catch a ferry to',
        'Take a domestic flight to',
        'Travel by train to'
    ];
    
    // Get activities for the selected traveler type
    const travelerActivities = activitySuggestions[selections.travelerType] || activitySuggestions['balanced'];
    
    // Add recommended destinations into the mix
    const destinationNames = recommendations.destinations.map(dest => dest.name);
    
    // Distribute the recommended activities across the days
    const distributedActivities = [...recommendedActivities]; // Copy the array
    let specificActivityIndex = 0;

    // Create day-by-day itinerary
    for (let day = 1; day <= daysCount; day++) {
        // Determine which destination to use for this day
        const destinationIndex = Math.min(Math.floor((day - 1) / Math.ceil(daysCount / destinationNames.length)), destinationNames.length - 1);
        const destination = destinationNames[destinationIndex] || 'Your destination';
        
        const isFirstDay = day === 1;
        const isLastDay = day === daysCount;
        const isTransferDay = (day > 1 && destinationIndex !== Math.min(Math.floor((day - 2) / Math.ceil(daysCount / destinationNames.length)), destinationNames.length - 1));
        
        // Prepare day activities
        const dayActivities = [];
        
        // Add arrival info for first day
        if (isFirstDay) {
            dayActivities.push('🛬 Arrive at ' + destination);
            dayActivities.push(mealSuggestions.lunch[Math.floor(Math.random() * mealSuggestions.lunch.length)]);
        } 
        // Add transfer info if changing destinations
        else if (isTransferDay) {
            const transferMethod = transportSuggestions[Math.floor(Math.random() * transportSuggestions.length)];
            dayActivities.push(`🚗 ${transferMethod} ${destination}`);
            dayActivities.push('🏨 Check in to your accommodation');
        }
        // Add morning activities for the last day (but not departure yet)
        else if (isLastDay) {
            // Add a breakfast suggestion
            dayActivities.push('🍳 ' + mealSuggestions.breakfast[Math.floor(Math.random() * mealSuggestions.breakfast.length)]);
            
            // Add some light morning activities before departure
            dayActivities.push('🛍️ Last-minute souvenir shopping');
            dayActivities.push('☕ Final visit to a favorite local cafe');
        } 
        // Regular day
        else {
            // Add a breakfast suggestion
            dayActivities.push('🍳 ' + mealSuggestions.breakfast[Math.floor(Math.random() * mealSuggestions.breakfast.length)]);
        }
        
        // Don't add specific recommended attractions on the last day
        if (!isLastDay) {
            // Add 1-2 specific recommended attractions if available
            for (let i = 0; i < 2; i++) {
                if (specificActivityIndex < distributedActivities.length) {
                    // Only add if not already in the day's activities
                    const specificActivity = distributedActivities[specificActivityIndex++];
                    if (!dayActivities.includes(specificActivity)) {
                        dayActivities.push('🏛️ ' + specificActivity);
                    }
                    
                    // Reset index if we've used all specific activities
                    if (specificActivityIndex >= distributedActivities.length) {
                        specificActivityIndex = 0;
                    }
                }
            }
        }
        
        // Add a lunch suggestion if needed
        if (!isLastDay && !isFirstDay && dayActivities.length < 4) {
            dayActivities.push('🍽️ ' + mealSuggestions.lunch[Math.floor(Math.random() * mealSuggestions.lunch.length)]);
        }
        
        // Fill remaining slots with traveler type activities for non-last days
        if (!isLastDay) {
            while (dayActivities.length < 4) {
                // Get a random activity that's not already added
                let activity;
                do {
                    activity = travelerActivities[Math.floor(Math.random() * travelerActivities.length)];
                } while (dayActivities.includes('🌴 ' + activity));
                
                dayActivities.push('🌴 ' + activity);
            }
            
            // Add a dinner or evening activity if not the last day
            dayActivities.push('🍷 ' + mealSuggestions.dinner[Math.floor(Math.random() * mealSuggestions.dinner.length)]);
            dayActivities.push('🌙 ' + eveningActivities[Math.floor(Math.random() * eveningActivities.length)]);
        }
        // For the last day, ensure packing and departure are the LAST activities
        else {
            // Add some activities but ensure we leave room for packing/departure
            while (dayActivities.length < 3) {
                // Get a random activity that's not already added
                let activity;
                do {
                    activity = travelerActivities[Math.floor(Math.random() * travelerActivities.length)];
                } while (dayActivities.includes('🌴 ' + activity));
                
                dayActivities.push('🌴 ' + activity);
            }
            
            // Always add these as the FINAL two activities for the last day
            dayActivities.push('🧳 Pack and prepare for departure');
            dayActivities.push('🛫 Depart from ' + destination);
        }
        
        // Get a random image for this traveler type
        const dayImage = getRandomImage(selections.travelerType);
        
        // Create the day card
        itineraryHTML += `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="card day-card">
                        <div class="card-header bg-${day % 2 === 0 ? 'primary' : 'info'} text-white">
                            <h5 class="mb-0">Day ${day}: ${destination}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="${dayImage}" class="img-fluid rounded day-image" alt="Day ${day} activity">
                                </div>
                                <div class="col-md-8">
                                    <ul class="day-activities">
                                        ${dayActivities.map(activity => `<li>${activity}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    itineraryHTML += '</div></div>';
    return itineraryHTML;
}

// Function to get a random hero image based on selections
function getHeroImage(selections) {
    let baseImage = 'assets/images/destinations/thailand.jpg'; // Default
    
    // If we have a selected destination with an image, use it
    if (selections.destination && selections.destination !== 'anywhere' && currentDestination && currentDestination.main_image_url) {
        return currentDestination.main_image_url;
    }
    
    // If no specific destination, use an image based on traveler type
    if (selections.travelerType) {
        const travelerTypeImages = {
            'culture': 'assets/images/hero-images/culture-hero.jpg',
            'adventure': 'assets/images/hero-images/adventure-hero.jpg',
            'relax': 'assets/images/hero-images/relax-hero.jpg',
            'foodie': 'assets/images/hero-images/food-hero.jpg',
            'nightlife': 'assets/images/hero-images/nightlife-hero.jpg',
            'balanced': 'assets/images/hero-images/balanced-hero.jpg'
        };
        
        if (travelerTypeImages[selections.travelerType]) {
            return travelerTypeImages[selections.travelerType];
        }
    }
    
    return baseImage;
}

// Generate personalized recommendations based on selections
function generateRecommendations(selections) {
    const recommendations = {
        summary: '',
        destinations: [],
        activities: []
    };
    
    // Get the selected destination if any
    let selectedDestination = null;
    if (selections.destination && selections.destination !== 'anywhere') {
        selectedDestination = allDestinations.find(d => d.destination_id == selections.destination);
    }
    
    // Set default summary based on traveler type
    let baseSummary = "A customized travel experience based on your preferences.";
    
    // Get traveler-type specific description
    if (selectedDestination && selections.travelerType) {
        const descriptionField = `${selections.travelerType}_description`;
        if (selectedDestination[descriptionField]) {
            baseSummary = selectedDestination[descriptionField];
        }
    }
    
    recommendations.summary = baseSummary;
    
    // For "Surprise Me" option, recommend random destinations
    if (selections.destination === 'anywhere') {
        // Get available destinations, excluding general entry (usually ID 1)
        const availableDestinations = allDestinations
            .filter(d => d.destination_id != 1) // Exclude the general entry
            .sort(() => Math.random() - 0.5); // Random sort
        
        // Get destinations that match the traveler type preference
        const matchingDestinations = availableDestinations.filter(d => {
            const descriptionField = `${selections.travelerType}_description`;
            return d[descriptionField] && d[descriptionField].trim() !== '';
        });
        
        // Use matching destinations if we have enough, otherwise use any available ones
        const destinationsToUse = matchingDestinations.length >= 3 ? 
            matchingDestinations : availableDestinations;
        
        // Take up to 3 random destinations
        const randomDests = destinationsToUse.slice(0, 3);
        recommendations.destinations = randomDests.map(d => ({
            name: d.location_name,
            reason: "Recommended based on your preferences",
            image: d.main_image_url || `assets/images/destinations/${d.location_name.toLowerCase().replace(/\s+/g, '-')}.jpg`
        }));
    }
    // For specific destination, use it and potentially add related destinations
    else if (selectedDestination) {
        // Add the selected destination first
        recommendations.destinations.push({
            name: selectedDestination.location_name,
            reason: "Your selected destination",
            image: selectedDestination.main_image_url || `assets/images/destinations/${selectedDestination.location_name.toLowerCase().replace(/\s+/g, '-')}.jpg`
        });
        
        // Update summary to include selected destination name
        recommendations.summary = recommendations.summary.replace(
            /travel|destination/i, 
            selectedDestination.location_name
        );
        
        // Potentially add nearby or related destinations if it's a longer trip
        if (['week', 'twoweeks', 'extended'].includes(selections.duration)) {
            // In a real implementation, this would fetch from the database
            // based on geography, but we'll just get random ones here
            const otherDestinations = allDestinations
                .filter(d => d.destination_id != selectedDestination.destination_id && d.destination_id != 1)
                .filter(d => d.country === selectedDestination.country) // Same country
                .sort(() => Math.random() - 0.5)
                .slice(0, ['week'].includes(selections.duration) ? 1 : 2); // 1 for week, 2 for longer
            
            recommendations.destinations = [
                ...recommendations.destinations,
                ...otherDestinations.map(d => ({
                    name: d.location_name,
                    reason: `Complements your trip to ${selectedDestination.location_name}`,
                    image: d.main_image_url || `assets/images/destinations/${d.location_name.toLowerCase().replace(/\s+/g, '-')}.jpg`
                }))
            ];
        }
    }
    
    // Add activities based on traveler type and destination using attraction data
    const travelerTypeCategories = {
        'culture': ['Religious', 'Historical', 'Cultural'],
        'adventure': ['Nature', 'Wildlife', 'Island'],
        'relax': ['Beach', 'Nature'],
        'foodie': ['Cultural', 'Shopping'],
        'nightlife': ['Entertainment', 'Shopping'],
        'balanced': ['Historical', 'Beach', 'Cultural', 'Nature']
    };
    
    // Get relevant categories for the selected traveler type
    const relevantCategories = travelerTypeCategories[selections.travelerType] || [];
    
    // Get attractions based on traveler type and destination
    let filteredAttractions = [];
    
    if (typeof allAttractions !== 'undefined') {
        // If a specific destination is selected
        if (selectedDestination) {
            // First try to get attractions for the specific destination
            filteredAttractions = allAttractions.filter(attraction => 
                attraction.destination_id == selectedDestination.destination_id && 
                relevantCategories.includes(attraction.category)
            );
            
            // If not enough attractions found, include attractions from the country
            if (filteredAttractions.length < 3) {
                // Get attractions from destinations in the same country
                const countryAttractions = allAttractions.filter(attraction => {
                    const attractionDestination = allDestinations.find(d => d.destination_id == attraction.destination_id);
                    return attractionDestination && 
                           attractionDestination.country === selectedDestination.country &&
                           attraction.destination_id != selectedDestination.destination_id &&
                           relevantCategories.includes(attraction.category);
                });
                
                // Combine with the destination-specific attractions
                filteredAttractions = [...filteredAttractions, ...countryAttractions];
            }
        }
        // If "anywhere" is selected or no destination-specific attractions found
        if (filteredAttractions.length < 3) {
            // Get some general attractions that match the traveler type
            const generalAttractions = allAttractions.filter(attraction => 
                relevantCategories.includes(attraction.category)
            ).sort(() => 0.5 - Math.random()); // Random order
            
            // Add these to any destination-specific attractions already found
            filteredAttractions = [...filteredAttractions, ...generalAttractions];
        }
        
        // Limit to 5 attractions
        filteredAttractions = filteredAttractions.slice(0, 5);
        
        // Add attractions to activities
        filteredAttractions.forEach(attraction => {
            // Create a short description from the attraction data
            let activityText = `Visit ${attraction.name}`;
            if (attraction.description) {
                // Get first sentence of description
                const firstSentence = attraction.description.split('.')[0];
                activityText += ` - ${firstSentence}`;
            }
            recommendations.activities.push(activityText);
        });
    }
    
    // Fallback to generic recommendations if no attractions found
    if (recommendations.activities.length === 0) {
        switch (selections.travelerType) {
            case 'culture':
                recommendations.activities.push(
                    "Visit temples and historical sites",
                    "Participate in traditional cooking classes",
                    "Attend cultural performances",
                    "Explore museums and art galleries"
                );
                break;
            case 'adventure':
                recommendations.activities.push(
                    "Go hiking or trekking in natural areas",
                    "Try rock climbing",
                    "Experience water activities like rafting",
                    "Explore caves and natural parks"
                );
                break;
            case 'relax':
                recommendations.activities.push(
                    "Enjoy spa and wellness treatments",
                    "Relax on pristine beaches",
                    "Practice yoga or meditation",
                    "Take scenic walks in nature"
                );
                break;
            case 'foodie':
                recommendations.activities.push(
                    "Try local street food markets",
                    "Take cooking classes",
                    "Visit food festivals",
                    "Experience authentic restaurants"
                );
                break;
            case 'nightlife':
                recommendations.activities.push(
                    "Explore vibrant bar districts",
                    "Attend live music venues",
                    "Experience local night markets",
                    "Join evening entertainment shows"
                );
                break;
            case 'balanced':
                recommendations.activities.push(
                    "Mix cultural sites with relaxation time",
                    "Try local cuisine at a variety of venues",
                    "Balance adventure activities with downtime",
                    "Experience both city and natural attractions"
                );
                break;
        }
    }
    
    // Customize based on duration
    let maxDestinations = 3; // Default
    switch (selections.duration) {
        case 'weekend':
            recommendations.summary = `A quick ${selections.durationName} to ${recommendations.destinations[0]?.name || 'your destination'}.`;
            maxDestinations = 1;
            break;
        case 'short':
            maxDestinations = 2;
            break;
        case 'week':
            maxDestinations = 3;
            break;
        case 'twoweeks':
        case 'extended':
            maxDestinations = 4;
            break;
    }
    
    // Limit destinations based on duration
    recommendations.destinations = recommendations.destinations.slice(0, maxDestinations);
    
    // Adjust for budget
    const budgetLevel = parseInt(selections.budget);
    if (budgetLevel <= 2) { // Budget options
        recommendations.summary = "An affordable " + recommendations.summary.toLowerCase();
        recommendations.activities.push("Focus on free attractions and affordable experiences");
    } else if (budgetLevel >= 4) { // Luxury options
        recommendations.summary = "A premium " + recommendations.summary.toLowerCase();
        recommendations.activities.push("Enjoy luxury accommodations and exclusive experiences");
    }
    
    // Companion adjustments
    switch (selections.companion) {
        case 'family-kids':
            recommendations.activities.push(
                "Visit kid-friendly attractions",
                "Enjoy family-friendly beaches with calm waters",
                "Participate in interactive activities suitable for children"
            );
            break;
        case 'solo':
            recommendations.activities.push(
                "Stay in social hostels to meet other travelers",
                "Join group tours for shared experiences",
                "Explore at your own pace without compromises"
            );
            break;
        case 'couple':
            recommendations.activities.push(
                "Enjoy romantic sunset dinners",
                "Book couple's spa treatments",
                "Take private tours for intimate experiences"
            );
            break;
        case 'friends':
            recommendations.activities.push(
                "Enjoy group-friendly activities and dining",
                "Experience local nightlife together",
                "Consider shared accommodations for quality time"
            );
            break;
        case 'family':
            recommendations.activities.push(
                "Visit attractions that appeal to all ages",
                "Choose accommodations with family rooms or connecting options",
                "Plan some free time for individual interests"
            );
            break;
        case 'group':
            recommendations.activities.push(
                "Book group tours with advance reservations",
                "Consider private transport for convenience",
                "Look for restaurants that can accommodate large parties"
            );
            break;
    }
    
    return recommendations;
}

document.addEventListener('DOMContentLoaded', function() { 
    console.log("DOM loaded, initializing quiz...");
    
    // Reset budget descriptions
    resetBudgetDescriptions();   
    
    // Get all quiz slides
    const slides = document.querySelectorAll('.quiz-slide');
    console.log(`Found ${slides.length} quiz slides`);
    
    if (slides.length === 0) {
        console.error("No quiz slides found! Check your HTML structure.");
        return;
    }
    
    const totalSteps = slides.length;
    const totalStepsEl = document.getElementById('totalSteps');
    if (totalStepsEl) {
        totalStepsEl.textContent = totalSteps;
    } else {
        console.error("Total steps element not found");
    }
    
    // Get navigation buttons
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    console.log(`Found ${nextButtons.length} next buttons and ${prevButtons.length} previous buttons`);
    
    if (nextButtons.length === 0) {
        console.error("No next buttons found! Check your HTML for buttons with class 'next-step'");
    }
    
    // Progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (!progressBar) {
        console.error("Progress bar not found!");
    }
    
    // Initialize current step
    let currentStep = 1;
    
    // Add destination-specific event handlers
    document.querySelectorAll('.quiz-slide[data-step="1"] .option-card').forEach(card => {
        card.addEventListener('click', function() {
            console.log("Destination card clicked:", this);
            const destId = this.getAttribute('data-value');
            if (destId && destId !== 'anywhere') {
                // Find the destination object
                currentDestination = allDestinations.find(d => d.destination_id == destId);
                if (currentDestination) {
                    console.log("Selected destination:", currentDestination.location_name);
                    updateBudgetDescriptions();
                } else {
                    console.error(`Destination with ID ${destId} not found in allDestinations array`);
                    resetBudgetDescriptions();
                }
            } else {
                // For "anywhere" selection, reset budget information
                console.log("'Anywhere' option selected");
                currentDestination = null;
                resetBudgetDescriptions();
            }
        });
    });
    
    // Handle Next button clicks
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log(`Next button clicked. Current step: ${currentStep}`);
            
            // Get current slide
            const currentSlide = document.querySelector(`.quiz-slide[data-step="${currentStep}"]`);
            if (!currentSlide) {
                console.error(`Current slide (step ${currentStep}) not found`);
                return;
            }
            
            // Validate selection for current step
            if (!validateStep(currentStep)) {
                alert('Please make a selection before continuing.');
                return;
            }
            
            // Hide current slide
            currentSlide.classList.remove('active');
            
            // Increment current step
            currentStep++;
            console.log(`Moving to step ${currentStep}`);
            
            // Update current step display
            const currentStepEl = document.getElementById('currentStep');
            if (currentStepEl) {
                currentStepEl.textContent = currentStep;
            }
            
            // Update progress bar
            if (progressBar) {
                const progressPercentage = (currentStep / totalSteps) * 100;
                progressBar.style.width = `${progressPercentage}%`;
                progressBar.setAttribute('aria-valuenow', progressPercentage);
            }
            
            // Show next slide
            const nextSlide = document.querySelector(`.quiz-slide[data-step="${currentStep}"]`);
            if (nextSlide) {
                nextSlide.classList.add('active');
            } else {
                console.error(`Next slide (step ${currentStep}) not found`);
                // If we're at the end, show results
                if (currentStep > totalSteps) {
                    console.log("Reached end of quiz, showing results");
                    showResults();
                } else {
                    // Something went wrong, try to recover
                    currentStep--;
                    currentSlide.classList.add('active');
                    alert("Error loading the next question. Please try again.");
                }
            }
        });
    });
    
    // Handle Previous button clicks
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log(`Previous button clicked. Current step: ${currentStep}`);
            
            // Get current slide
            const currentSlide = document.querySelector(`.quiz-slide[data-step="${currentStep}"]`);
            if (!currentSlide) {
                console.error(`Current slide (step ${currentStep}) not found`);
                return;
            }
            
            // Hide current slide
            currentSlide.classList.remove('active');
            
            // Decrement current step
            currentStep--;
            if (currentStep < 1) currentStep = 1; // Don't go below 1
            console.log(`Moving back to step ${currentStep}`);
            
            // Update current step display
            const currentStepEl = document.getElementById('currentStep');
            if (currentStepEl) {
                currentStepEl.textContent = currentStep;
            }
            
            // Update progress bar
            if (progressBar) {
                const progressPercentage = (currentStep / totalSteps) * 100;
                progressBar.style.width = `${progressPercentage}%`;
                progressBar.setAttribute('aria-valuenow', progressPercentage);
            }
            
            // Show previous slide
            const prevSlide = document.querySelector(`.quiz-slide[data-step="${currentStep}"]`);
            if (prevSlide) {
                prevSlide.classList.add('active');
            } else {
                console.error(`Previous slide (step ${currentStep}) not found`);
                // Try to recover
                currentStep++;
                currentSlide.classList.add('active');
                alert("Error loading the previous question. Please try again.");
            }
        });
    });
    
    // Handle option card selection
    const optionCards = document.querySelectorAll('.option-card');
    console.log(`Found ${optionCards.length} option cards to attach click events to`);
    
    optionCards.forEach(card => {
        card.addEventListener('click', function() {
            console.log("Option card clicked:", this);
            
            // For single-select questions, deselect all cards in the same step first
            const step = this.closest('.quiz-slide')?.getAttribute('data-step');
            if (!step) {
                console.error("Could not find parent slide for this option card");
                return;
            }
            
            console.log(`This card is in step ${step}`);
            const stepCards = document.querySelectorAll(`.quiz-slide[data-step="${step}"] .option-card`);
            
            stepCards.forEach(stepCard => {
                stepCard.classList.remove('selected');
            });
            
            // Select clicked card
            this.classList.add('selected');
        });
    });
    
    // Handle multi-select options
    const multiOptions = document.querySelectorAll('.multi-option');
    console.log(`Found ${multiOptions.length} multi-select options`);
    
    multiOptions.forEach(option => {
        option.addEventListener('click', function() {
            console.log("Multi-option clicked:", this);
            this.classList.toggle('selected');
        });
    });
    
    // Handle budget range slider
    const budgetSlider = document.getElementById('budgetRange');
    if (budgetSlider) {
        console.log("Budget slider found, attaching event listener");
        
        budgetSlider.addEventListener('input', function() {
            console.log("Budget slider value changed to:", this.value);
            
            // Update budget label based on value
            const budgetLabels = [
                'Budget Friendly',
                'Comfort on a Budget',
                'Mid-range',
                'Upper Mid-range',
                'Luxury'
            ];
            
            const budgetLabel = document.getElementById('budgetLabel');
            if (budgetLabel) {
                budgetLabel.textContent = budgetLabels[this.value - 1];
            } else {
                console.error("Budget label element not found");
            }
            
            // Show corresponding description
            for (let i = 1; i <= 5; i++) {
                const descEl = document.getElementById(`budget-${i}`);
                if (descEl) {
                    descEl.style.display = (i == this.value) ? 'block' : 'none';
                } else {
                    console.error(`Budget description element budget-${i} not found`);
                }
            }
        });
    } else {
        console.error("Budget slider not found!");
    }
    
    // Process and show the final results
    window.showResults = function() {
        console.log("Showing results...");
        
        // Hide all slides
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // Collect all selections
        const selections = {};
        
        // Destination (Step 1)
        const destinationCard = document.querySelector('.quiz-slide[data-step="1"] .option-card.selected');
        if (destinationCard) {
            selections.destination = destinationCard.getAttribute('data-value');
            selections.destinationName = destinationCard.querySelector('h5')?.textContent || 'Unknown';
        }
        
        // Traveler Type (Step 2)
        const travelerCard = document.querySelector('.quiz-slide[data-step="2"] .option-card.selected');
        if (travelerCard) {
            selections.travelerType = travelerCard.getAttribute('data-value');
            selections.travelerTypeName = travelerCard.querySelector('h5')?.textContent || 'Unknown';
        }
        
        // Duration (Step 3)
        const durationCard = document.querySelector('.quiz-slide[data-step="3"] .option-card.selected');
        if (durationCard) {
            selections.duration = durationCard.getAttribute('data-value');
            selections.durationName = durationCard.querySelector('h5')?.textContent || 'Unknown';
        }
        
        // Companion (Step 4)
        const companionCard = document.querySelector('.quiz-slide[data-step="4"] .option-card.selected');
        if (companionCard) {
            selections.companion = companionCard.getAttribute('data-value');
            selections.companionName = companionCard.querySelector('h5')?.textContent || 'Unknown';
        }
        
        // Budget (Step 5)
        const budgetEl = document.getElementById('budgetRange');
        if (budgetEl) {
            selections.budget = budgetEl.value;
            selections.budgetName = document.getElementById('budgetLabel')?.textContent || 'Mid-range';
        }
        
        // Travel Dates (Step 6)
        const datesCard = document.querySelector('.quiz-slide[data-step="6"] .option-card.selected');
        if (datesCard) {
            selections.travelDates = datesCard.getAttribute('data-value');
            selections.travelDatesName = datesCard.querySelector('h5')?.textContent || 'Unknown';
            
            if (selections.travelDates === 'specific-dates') {
                selections.startDate = document.getElementById('startDate')?.value;
                selections.endDate = document.getElementById('endDate')?.value;
            }
        }
        
        console.log("User selections:", selections);
        
        // Create recommendations based on selections
        const recommendations = generateRecommendations(selections);
        
        // Show result section
        let resultContainer = document.querySelector('.result-container');
        if (resultContainer) {
            resultContainer.classList.add('active');
        } else {
            // Create result container if it doesn't exist
            console.log("Creating new result container");
            const newResultContainer = document.createElement('div');
            newResultContainer.classList.add('result-container', 'active');
            document.querySelector('.quiz-slides')?.appendChild(newResultContainer);
            resultContainer = newResultContainer;
        }
        
        // Get hero image based on selections
        const heroImage = getHeroImage(selections);
        
        // Generate destinations section with images
        let recommendedDestinations = '';
        if (recommendations.destinations && recommendations.destinations.length > 0) {
            recommendedDestinations = `
                <div class="mt-5">
                    <h4 class="mb-4 text-center">Your Destinations</h4>
                    <div class="row">
                        ${recommendations.destinations.map((dest, index) => `
                            <div class="col-md-${12 / Math.min(recommendations.destinations.length, 3)} mb-4">
                                <div class="card h-100 destination-card">
                                    <img src="${dest.image}" class="card-img-top destination-img" alt="${dest.name}" 
                                         onerror="this.src='assets/images/destinations/placeholder.jpg'">
                                    <div class="card-body">
                                        <h5 class="card-title">${dest.name}</h5>
                                        <p class="card-text">${dest.reason}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        // Generate highlights section
        let highlightsSection = '';
        if (recommendations.activities && recommendations.activities.length > 0) {
            // Group activities into two columns
            const halfLength = Math.ceil(recommendations.activities.length / 2);
            const firstColumn = recommendations.activities.slice(0, halfLength);
            const secondColumn = recommendations.activities.slice(halfLength);
            
            highlightsSection = `
                <div class="mt-5">
                    <h4 class="mb-4 text-center">Trip Highlights</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                ${firstColumn.map(activity => `
                                    <li class="list-group-item">
                                        <i class="fas fa-check-circle text-success me-2"></i> ${activity}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group">
                                ${secondColumn.map(activity => `
                                    <li class="list-group-item">
                                        <i class="fas fa-check-circle text-success me-2"></i> ${activity}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Generate day-by-day itinerary
        const dayByDayItinerary = generateDayByDayItinerary(selections, recommendations);
        
        // Create the result HTML with new visualization
        resultContainer.innerHTML = `
            <div class="container">
                <h2 class="text-center mb-4">Your Personalized Travel Experience</h2>
                
                <!-- Hero Image -->
                <div class="hero-result position-relative mb-5">
                    <img src="${heroImage}" class="w-100 rounded hero-image" alt="Destination" 
                         style="height: 400px; object-fit: cover;"
                         onerror="this.src='assets/images/destinations/placeholder.jpg'">
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                         style="background: rgba(0,0,0,0.4); border-radius: 0.25rem;">
                        <div class="text-white text-center p-4">
                            <h3 class="display-5 fw-bold">${recommendations.summary}</h3>
                        </div>
                    </div>
                </div>
                
                <!-- Trip Summary Card -->
                <div class="card mb-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Trip Overview</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Travel Style:</strong> ${selections.travelerTypeName || 'Not specified'}</p>
                                <p><strong>Duration:</strong> ${selections.durationName || 'Not specified'}</p>
                                <p><strong>Traveling With:</strong> ${selections.companionName || 'Not specified'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Budget Level:</strong> ${selections.budgetName || 'Not specified'}</p>
                                ${selections.travelDates === 'specific-dates' ? 
                                    `<p><strong>Travel Dates:</strong> ${selections.startDate} to ${selections.endDate}</p>` : 
                                    `<p><strong>Travel Timeframe:</strong> ${selections.travelDatesName || 'Not specified'}</p>`
                                }
                                <p><strong>Destination:</strong> ${selections.destinationName || 'Any destination that matches your preferences'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Destinations Section -->
                ${recommendedDestinations}
                
                <!-- Highlights Section -->
                ${highlightsSection}
                
                <!-- Day-by-Day Itinerary -->
                ${dayByDayItinerary}
                
                <!-- Action Buttons -->
                <div class="text-center mt-5 mb-4">
                    <a href="itinerary.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-calendar-alt me-2"></i> View in Itinerary Planner
                    </a>
                    <button class="btn btn-outline-secondary btn-lg" onclick="resetQuiz()">
                        <i class="fas fa-redo me-2"></i> Start Over
                    </button>
                </div>
            </div>
        `;
        
        // Add additional styles specific to the results page
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Timeline Styles */
            .timeline {
                position: relative;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .timeline-item {
                padding: 10px 40px;
                position: relative;
                background-color: inherit;
                width: 100%;
            }
            
            .timeline-marker {
                content: '';
                position: absolute;
                width: 25px;
                height: 25px;
                background-color: white;
                border: 4px solid #3498db;
                top: 0;
                border-radius: 50%;
                z-index: 1;
                left: 0;
            }
            
            .timeline::after {
                content: '';
                position: absolute;
                width: 4px;
                background-color: #ddd;
                top: 0;
                bottom: 0;
                left: 10px;
                margin-left: 0;
            }
            
            .timeline-content {
                padding: 0 20px;
                position: relative;
            }
            
            .day-card {
                margin-bottom: 20px;
                transition: transform 0.3s ease;
            }
            
            .day-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .day-image {
                height: 180px;
                object-fit: cover;
            }
            
            .day-activities {
                list-style-type: none;
                padding-left: 0;
            }
            
            .day-activities li {
                padding: 8px 0;
                border-bottom: 1px dashed #eee;
            }
            
            .day-activities li:last-child {
                border-bottom: none;
            }
            
            /* Destination Cards */
            .destination-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                border: none;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .destination-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            }
            
            .destination-img {
                height: 200px;
                object-fit: cover;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .timeline-item {
                    padding-left: 20px;
                    padding-right: 10px;
                }
                
                .timeline::after {
                    left: 5px;
                }
                
                .timeline-marker {
                    left: -5px;
                    width: 20px;
                    height: 20px;
                }
            }
        `;
        document.head.appendChild(styleElement);
        
        // Update progress to 100%
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.setAttribute('aria-valuenow', 100);
        }
        
        // Scroll to top of results
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
    
    // Reset quiz function
    window.resetQuiz = function() {
        console.log("Resetting quiz...");
        
        // Hide result container
        const resultContainer = document.querySelector('.result-container');
        if (resultContainer) {
            resultContainer.classList.remove('active');
        }
        
        // Reset to first slide
        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === 0);
        });
        
        // Reset selections
        document.querySelectorAll('.option-card.selected').forEach(card => {
            card.classList.remove('selected');
        });
        
        document.querySelectorAll('.multi-option.selected').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Reset budget slider
        if (document.getElementById('budgetRange')) {
            document.getElementById('budgetRange').value = 3;
            const budgetLabel = document.getElementById('budgetLabel');
            if (budgetLabel) {
                budgetLabel.textContent = 'Mid-range';
            }
            
            for (let i = 1; i <= 5; i++) {
                const descEl = document.getElementById(`budget-${i}`);
                if (descEl) {
                    descEl.style.display = (i == 3) ? 'block' : 'none';
                }
            }
        }
        
        // Reset progress
        currentStep = 1;
        const currentStepEl = document.getElementById('currentStep');
        if (currentStepEl) {
            currentStepEl.textContent = 1;
        }
        
        if (progressBar) {
            progressBar.style.width = '12.5%';
            progressBar.setAttribute('aria-valuenow', 12.5);
        }
        
        // Scroll to top of quiz
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
});