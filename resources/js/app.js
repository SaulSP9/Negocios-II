import Alpine from 'alpinejs';
import { jsPDF } from 'jspdf';
import { applyPlugin } from 'jspdf-autotable';
applyPlugin(jsPDF);
window.jspdf = { jsPDF };
window.Alpine = Alpine;
Alpine.start();
