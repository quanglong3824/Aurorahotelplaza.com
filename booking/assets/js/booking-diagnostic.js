/**
 * Aurora Hotel Plaza - Booking Diagnostic Tool
 * Công cụ check lỗi toàn diện cho trang booking
 * Usage: Mở Console (F12) và chạy BookingDiagnostic.run()
 */

const BookingDiagnostic = {
    config: {
        apiEndpoint: './api/diagnostic-check.php',
        createBookingEndpoint: './api/create_booking.php',
        validateBookingEndpoint: './api/validate-booking.php',
        timeout: 30000,
        verbose: true
    },

    results: {
        timestamp: null,
        duration: 0,
        errors: [],
        warnings: [],
        info: {}
    },

    async run() {
        console.clear();
        console.groupCollapsed('🔍 BOOKING DIAGNOSTIC TOOL');
        console.log('%c🚀 Bắt đầu diagnostic...', 'color: #3b82f6; font-weight: bold; font-size: 14px');
        this.results.timestamp = new Date().toISOString();
        const startTime = performance.now();

        try {
            await this.checkPHPEnvironment();
            this.checkFormData();
            await this.checkSession();
            await this.testAPIEndpoints();
            this.generateReport(startTime);
        } catch (error) {
            console.error('❌ Diagnostic failed:', error);
        }

        console.groupEnd();
        return this.results;
    },

    async checkPHPEnvironment() {
        console.groupCollapsed('📌 1. Kiểm tra PHP Environment');
        
        try {
            const response = await fetch(this.config.apiEndpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

            const data = await response.json();

            if (data.success) {
                console.log('%c✅ PHP Environment OK', 'color: #10b981');
                
                if (data.php_environment) {
                    console.log(`   PHP Version: ${data.php_environment.php_version}`);
                    console.log(`   Memory Limit: ${data.php_environment.memory_limit}`);
                }

                if (data.database_connection) {
                    console.log('%c✅ Database Connection OK', 'color: #10b981');
                    console.log(`   Database: ${data.database_connection.database}`);
                    console.log(`   Host: ${data.database_connection.host}`);
                }

                if (data.columns && data.columns.bookings) {
                    const bookingsColumns = data.columns.bookings;
                    const missingColumns = bookingsColumns.filter(col => !col.exists);
                    
                    if (missingColumns.length > 0) {
                        console.error('%c❌ Missing columns in bookings table:', 'color: #ef4444; font-weight: bold');
                        missingColumns.forEach(col => console.error(`   - ${col.column_name}`));
                        this.results.errors.push({ section: 'DATABASE_COLUMNS', message: 'Missing columns' });
                    } else {
                        console.log(`%c✅ All ${bookingsColumns.length} columns exist`, 'color: #10b981');
                    }
                }

                if (data.sample_booking_test) {
                    const test = data.sample_booking_test;
                    if (test.success) {
                        console.log('%c✅ Sample Booking Test PASSED', 'color: #10b981; font-weight: bold');
                    } else {
                        console.error('%c❌ Sample Booking Test FAILED:', 'color: #ef4444; font-weight: bold');
                        console.error(`   Error: ${test.error}`);
                        this.results.errors.push({ section: 'SAMPLE_BOOKING_TEST', message: test.error });
                    }
                }

                this.results.info.php_environment = data;
            } else {
                console.error('%c❌ Diagnostic API failed', 'color: #ef4444; font-weight: bold');
                this.results.errors.push({ section: 'DIAGNOSTIC_API', message: data.message });
            }
        } catch (error) {
            console.error('%c❌ Cannot connect to Diagnostic API', 'color: #ef4444; font-weight: bold');
            console.error(`   URL: ${this.config.apiEndpoint}`);
            this.results.errors.push({ section: 'DIAGNOSTIC_API', message: error.message });
        }

        console.groupEnd();
    },

    checkFormData() {
        console.groupCollapsed('📝 2. Kiểm tra Form Data');
        const form = document.querySelector('form');
        
        if (!form) {
            console.error('%c❌ No form found', 'color: #ef4444; font-weight: bold');
            this.results.errors.push({ section: 'FORM_DATA', message: 'No form element' });
        } else {
            console.log('%c✅ Form found', 'color: #10b981');
        }

        const fields = ['check_in_date', 'check_out_date', 'num_adults', 'num_children', 'guest_name', 'guest_email', 'room_type_id'];
        console.log('\\n📋 Required fields:');
        fields.forEach(f => {
            const field = document.querySelector(`[name="${f}"]`);
            if (field) console.log(`%c   ✅ ${f}`, 'color: #10b981', field.value || '(empty)');
            else console.warn(`%c   ⚠️ ${f}: NOT FOUND`, 'color: #f59e0b');
        });
        console.groupEnd();
    },

    async checkSession() {
        console.groupCollapsed('👤 3. Kiểm tra Session');
        const userId = localStorage.getItem('user_id');
        if (userId) console.log('%c✅ User logged in:', 'color: #10b981', userId);
        else console.warn('%c⚠️ Guest booking mode', 'color: #f59e0b');
        console.groupEnd();
    },

    async testAPIEndpoints() {
        console.groupCollapsed('🔌 4. Kiểm tra API Endpoints');
        const endpoints = [
            { name: 'Create Booking', url: this.config.createBookingEndpoint },
            { name: 'Validate Booking', url: this.config.validateBookingEndpoint }
        ];

        for (const ep of endpoints) {
            try {
                const res = await fetch(ep.url, { method: 'HEAD' });
                if (res.ok) console.log(`%c   ✅ ${ep.name}`, 'color: #10b981');
                else console.error(`%c   ❌ ${ep.name}: ${res.status}`, 'color: #ef4444');
            } catch (e) {
                console.error(`%c   ❌ ${ep.name}: ${e.message}`, 'color: #ef4444');
            }
        }
        console.groupEnd();
    },

    generateReport(startTime) {
        console.groupCollapsed('📈 5. Báo Cáo');
        const duration = performance.now() - startTime;
        console.log('\\n' + '='.repeat(60));
        console.log('%c📊 DIAGNOSTIC REPORT', 'color: #3b82f6; font-weight: bold; font-size: 16px');
        console.log('='.repeat(60));
        console.log(`⏱️ Duration: ${duration.toFixed(2)}ms`);
        console.log(`❌ Errors: ${this.results.errors.length}`);
        console.log(`⚠️ Warnings: ${this.results.warnings.length}`);
        console.log('='.repeat(60));
        
        if (this.results.errors.length === 0) {
            console.log('%c✅ NO ERRORS FOUND', 'color: #10b981; font-weight: bold');
        } else {
            console.log('%c❌ ERRORS FOUND:', 'color: #ef4444; font-weight: bold');
            this.results.errors.forEach((e, i) => console.error(`   ${i+1}. [${e.section}] ${e.message}`));
        }
        
        console.log('\\n💾 Results: window.bookingDiagnosticResults');
        console.groupEnd();
        window.bookingDiagnosticResults = this.results;
    }
};

if (window.location.pathname.includes('/booking/')) {
    console.log('\\n%c🔍 Booking Diagnostic loaded! Run: BookingDiagnostic.run()', 'color: #3b82f6; font-weight: bold');
}
