// assets/controllers/slideshow_controller.js
// Demonstrates Values API with change callbacks for state management

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['slide', 'counter', 'prevButton', 'nextButton']

    static values = {
        index: { type: Number, default: 0 },
        autoplay: { type: Boolean, default: false },
        interval: { type: Number, default: 5000 }
    }

    connect() {
        if (this.autoplayValue) {
            this.startAutoplay()
        }
    }

    disconnect() {
        // Always clean up intervals and listeners in disconnect()
        this.stopAutoplay()
    }

    next() {
        if (this.indexValue < this.slideTargets.length - 1) {
            this.indexValue++
        }
    }

    previous() {
        if (this.indexValue > 0) {
            this.indexValue--
        }
    }

    goToSlide(event) {
        this.indexValue = parseInt(event.params.index)
    }

    // Automatically called whenever indexValue changes
    indexValueChanged(newIndex, previousIndex) {
        this.slideTargets.forEach((element, index) => {
            element.hidden = index !== newIndex
        })

        this.counterTarget.textContent = `${newIndex + 1} / ${this.slideTargets.length}`

        // Update button states
        this.prevButtonTarget.disabled = newIndex === 0
        this.nextButtonTarget.disabled = newIndex === this.slideTargets.length - 1
    }

    // Automatically called whenever autoplayValue changes
    autoplayValueChanged(isAutoplay) {
        if (isAutoplay) {
            this.startAutoplay()
        } else {
            this.stopAutoplay()
        }
    }

    toggleAutoplay() {
        this.autoplayValue = !this.autoplayValue
    }

    startAutoplay() {
        this.timer = setInterval(() => {
            if (this.indexValue < this.slideTargets.length - 1) {
                this.indexValue++
            } else {
                this.indexValue = 0
            }
        }, this.intervalValue)
    }

    stopAutoplay() {
        if (this.timer) {
            clearInterval(this.timer)
            this.timer = null
        }
    }
}

/*
Usage in Twig:

<div data-controller="slideshow"
     data-slideshow-index-value="0"
     data-slideshow-autoplay-value="true"
     data-slideshow-interval-value="3000">
    
    <div data-slideshow-target="slide">Slide 1</div>
    <div data-slideshow-target="slide" hidden>Slide 2</div>
    <div data-slideshow-target="slide" hidden>Slide 3</div>
    
    <button data-slideshow-target="prevButton" 
            data-action="slideshow#previous">Previous</button>
    <span data-slideshow-target="counter">1 / 3</span>
    <button data-slideshow-target="nextButton"
            data-action="slideshow#next">Next</button>
    
    <button data-action="slideshow#toggleAutoplay">Toggle Autoplay</button>
</div>
*/
