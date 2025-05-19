// Fixed and properly structured version of custom-itinerary.js
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

// Generate personalized recommendations based on selections
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
            reason: "Recommended based on your preferences"
        }));
    }
    // For specific destination, use it and potentially add related destinations
    else if (selectedDestination) {
        // Add the selected destination first
        recommendations.destinations.push({
            name: selectedDestination.location_name,
            reason: "Your selected destination"
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
                    reason: `Complements your trip to ${selectedDestination.location_name}`
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
        
        let recommendedDestinations = '';
        if (recommendations.destinations && recommendations.destinations.length > 0) {
            recommendedDestinations = `
                <div class="mt-4">
                    <h4>Recommended Destinations</h4>
                    <ul class="list-group">
                        ${recommendations.destinations.map(dest => `
                            <li class="list-group-item">
                                <strong>${dest.name}</strong> - ${dest.reason}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        let recommendedActivities = '';
        if (recommendations.activities && recommendations.activities.length > 0) {
            recommendedActivities = `
                <div class="mt-4">
                    <h4>Suggested Activities</h4>
                    <ul class="list-group">
                        ${recommendations.activities.map(activity => `
                            <li class="list-group-item">${activity}</li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        resultContainer.innerHTML = `
            <h2>Your Personalized Itinerary</h2>
            <div class="result-card">
                <h3>Based on your preferences, we recommend:</h3>
                <p class="lead">${recommendations.summary}</p>
                <div class="summary">
                    <p><strong>Destination:</strong> ${selections.destinationName || 'Any destination that matches your preferences'}</p>
                    <p><strong>Travel Style:</strong> ${selections.travelerTypeName || 'Not specified'}</p>
                    <p><strong>Duration:</strong> ${selections.durationName || 'Not specified'}</p>
                    <p><strong>Budget Level:</strong> ${selections.budgetName || 'Not specified'}</p>
                    ${selections.travelDates === 'specific-dates' ? 
                        `<p><strong>Travel Dates:</strong> ${selections.startDate} to ${selections.endDate}</p>` : 
                        `<p><strong>Travel Timeframe:</strong> ${selections.travelDatesName || 'Not specified'}</p>`
                    }
                </div>
                
                ${recommendedDestinations}
                ${recommendedActivities}
                
                <div class="mt-4">
                    <a href="create-trip.php" class="btn btn-primary">Create This Trip</a>
                    <button class="btn btn-outline-secondary" onclick="resetQuiz()">Start Over</button>
                </div>
            </div>
        `;
        
        // Update progress to 100%
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.setAttribute('aria-valuenow', 100);
        }
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
    };
});